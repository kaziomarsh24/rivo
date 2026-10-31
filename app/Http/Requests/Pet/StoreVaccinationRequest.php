<?php

namespace App\Http\Requests\Pet;

use Illuminate\Foundation\Http\FormRequest;

class StoreVaccinationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'vaccine_type' => 'nullable|string|in:core,non_core,lifestyle',
            'administered_at' => 'required|date',
            'due_at' => 'required|date|after_or_equal:administered_at',
            'clinic_name' => 'nullable|string|max:255',
            'veterinarian_name' => 'nullable|string|max:255',
            'batch_number' => 'nullable|string|max:100',
            'certificate_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'certificate' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:10240',
            'notes' => 'nullable|string|max:1000',
        ];
    }
}
