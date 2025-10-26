<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvSettingsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Primary EV charging slot (Slot 5)
            'default_sell_time' => ['nullable', 'date_format:H:i'],
            'default_cap' => ['nullable', 'numeric', 'min:0', 'max:100'],
            
            // Additional time slots
            'sell_time_1' => ['nullable', 'date_format:H:i'],
            'cap_1' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'time_1_on' => ['nullable', 'boolean'],
            
            'sell_time_2' => ['nullable', 'date_format:H:i'],
            'cap_2' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'time_2_on' => ['nullable', 'boolean'],
            
            'sell_time_3' => ['nullable', 'date_format:H:i'],
            'cap_3' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'time_3_on' => ['nullable', 'boolean'],
            
            'sell_time_4' => ['nullable', 'date_format:H:i'],
            'cap_4' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'time_4_on' => ['nullable', 'boolean'],
            
            'sell_time_6' => ['nullable', 'date_format:H:i'],
            'cap_6' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'time_6_on' => ['nullable', 'boolean'],
            
            // Night time range
            'night_start' => ['nullable', 'date_format:H:i'],
            'night_end' => ['nullable', 'date_format:H:i'],
            
            // Battery Discharge to Grid Settings
            'discharge_enabled' => ['nullable', 'in:true'],
            'battery_size_wh' => ['nullable', 'numeric', 'min:1000', 'max:100000'],
            'discharge_rate_w' => ['nullable', 'numeric', 'min:100', 'max:10000'],
            'house_load_w' => ['nullable', 'numeric', 'min:0', 'max:5000'],
            'discharge_to_soc' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discharge_min_soc' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discharge_check_time' => ['nullable', 'date_format:H:i'],
            'discharge_stop_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'default_sell_time.date_format' => 'The default sell time must be in HH:MM format.',
            'default_cap.numeric' => 'The default cap must be a number.',
            'default_cap.min' => 'The default cap must be at least 0.',
            'default_cap.max' => 'The default cap must not exceed 100.',
            'night_start.date_format' => 'The night start time must be in HH:MM format.',
            'night_end.date_format' => 'The night end time must be in HH:MM format.',
        ];
    }
}
