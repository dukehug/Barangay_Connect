<?php

namespace App\Http\Requests;

use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResidentComplaintRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('resident') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'complaint_type' => ['required', 'string', 'max:255', Rule::exists('complaint_types', 'name')->where('is_active', true)],
            'content' => ['required', 'string', 'max:10000'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240', 'prohibited_with:uploads.complaint_attachment'],
            'uploads' => ['nullable', 'array:complaint_attachment'],
            'uploads.complaint_attachment' => ['nullable', 'uuid', Rule::exists('resident_document_uploads', 'token')
                ->where('resident_id', $this->user('resident')->id)->where('kind', 'complaint_attachment')
                ->where(fn (Builder $query) => $query->where('created_at', '>=', now()->subDay()))],
        ];
    }
}
