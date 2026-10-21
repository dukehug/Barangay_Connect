<?php

namespace App\Http\Requests;

use App\Models\DocumentRequest;
use App\Models\DocumentType;
use App\Models\RequestAttachment;
use App\Models\ResidentDocumentUpload;
use App\Models\ResourceItem;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ResidentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $record = $this->route('document');

        return $this->user('resident') !== null && (! $record instanceof DocumentRequest || $record->resident_id === $this->user('resident')->id);
    }

    protected function prepareForValidation(): void
    {
        $lines = $this->input('reservations', []);
        if (! is_array($lines)) {
            return;
        }
        $lines = array_filter($lines, fn (mixed $line): bool => ! is_array($line) || ($line['requested_quantity'] ?? 0) != 0);
        foreach ($lines as &$line) {
            if (! is_array($line)) {
                continue;
            }
            foreach (['starts_at', 'ends_at'] as $key) {
                if (is_string($line[$key] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $line[$key])) {
                    $line[$key] .= ':00+08:00';
                }
            }
        }
        $this->merge(['reservations' => array_values($lines)]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $record = $this->route('document');
        $category = $record instanceof DocumentRequest ? $record->workflowCategory() : DocumentType::find(is_scalar($this->input('document_type_id')) ? $this->input('document_type_id') : null)?->category;
        $rules = [
            'document_type_id' => ['required', 'integer', $record instanceof DocumentRequest ? Rule::in([$record->document_type_id]) : Rule::exists('document_types', 'id')->where('is_active', true)],
            'purpose' => ['required', 'string', 'max:5000'],
            'application_kind' => [$category === 'clearance' ? 'required' : 'exclude', Rule::in(['new', 'renewal'])],
            'uploads' => ['nullable', 'array:'.implode(',', array_keys(RequestAttachment::KINDS))],
            'files' => ['nullable', 'array:'.implode(',', array_keys(RequestAttachment::KINDS))],
            'reservations' => [$category === 'use_closure' ? 'required' : 'prohibited', 'array', 'max:50'],
            'reservations.*.resource_item_id' => ['required', 'integer', 'distinct', Rule::exists('resource_items', 'id')->where('is_active', true)],
            'reservations.*.requested_quantity' => ['required', 'integer', 'min:1', 'max:100000'],
            'reservations.*.starts_at' => ['required', 'date', 'after:now'],
            'reservations.*.ends_at' => ['required', 'date', 'after:reservations.*.starts_at'],
        ];
        $required = match ($category) {
            'clearance' => ['request_letter', $this->input('application_kind') === 'renewal' ? 'previous_clearance' : 'registration'],
            'certification' => ['voters_id', 'voter_registration_record', 'nbi_clearance', 'photo_id'],
            'use_closure' => ['request_letter'],
            default => ['photo_id'],
        };
        foreach (array_keys(RequestAttachment::KINDS) as $kind) {
            $exists = $record instanceof DocumentRequest && $record->attachments()->where('kind', $kind)->exists();
            $uploadToken = $this->input('uploads.'.$kind);
            $uploaded = is_string($uploadToken) && ResidentDocumentUpload::where('token', $uploadToken)
                ->where('resident_id', $this->user('resident')->id)->where('kind', $kind)
                ->where('created_at', '>=', now()->subDay())->whereHas('media')->exists();
            $rules['uploads.'.$kind] = [Rule::excludeIf(! in_array($kind, $required, true)), 'nullable', 'uuid', Rule::exists('resident_document_uploads', 'token')
                ->where('resident_id', $this->user('resident')->id)->where('kind', $kind)->where(fn (Builder $query) => $query->where('created_at', '>=', now()->subDay()))];
            $rules['files.'.$kind] = [in_array($kind, $required, true) && ! $exists && ! $uploaded ? 'required' : 'nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
        }

        return $rules;
    }

    /** @return array<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            foreach ($this->input('reservations', []) as $index => $line) {
                if (ResourceItem::find($line['resource_item_id'])?->kind === 'street' && (int) $line['requested_quantity'] !== 1) {
                    $validator->errors()->add('reservations.'.$index.'.requested_quantity', 'Select 1 for a street or road segment.');
                }
            }
        }];
    }
}
