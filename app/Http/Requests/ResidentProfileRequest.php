<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ResidentProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('resident') !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'gender' => ['required', Rule::in(['Male', 'Female'])],
            'resident_address' => ['required', 'string', 'max:255'],
            'marital_status' => ['required', Rule::in(['Single', 'Married', 'Widowed', 'Divorced'])],
            'blood_type' => ['nullable', Rule::in(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'])],
            'email' => ['required', 'email', 'max:255', Rule::unique('residents', 'email')->ignore($this->user('resident')->id)],
            'contact_number' => ['nullable', 'string', 'max:30', Rule::unique('residents', 'contact_number')->ignore($this->user('resident')->id)],
            'photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
