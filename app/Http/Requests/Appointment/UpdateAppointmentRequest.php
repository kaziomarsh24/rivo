<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAppointmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pet_id' => 'sometimes|exists:pets,id',
            'title' => 'sometimes|required|string|max:255',
            'type' => 'sometimes|required|string|max:100',
            'vet_name' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'datetime' => 'sometimes|required|date',
            'reminder' => 'sometimes|required|in:1_hour,1_day,1_week,none',
            'notes' => 'nullable|string',
            'status' => 'sometimes|in:upcoming,completed,cancelled',
        ];
    }
}
