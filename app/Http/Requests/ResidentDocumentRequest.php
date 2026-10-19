<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResidentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('resident') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'document_type_id' => ['required', 'integer', Rule::exists('document_types', 'id')->where('is_active', true)],
            'purpose' => ['required', 'string', 'max:5000'],
        ];
    }
}
