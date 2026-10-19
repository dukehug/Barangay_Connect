<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'complaint_type' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:10000'],
            'attachment' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
