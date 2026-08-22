<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Оставили только валидацию Email и указали правильную таблицу 'Users'
            'email' => [
                'required', 
                'string', 
                'lowercase', 
                'email', 
                'max:255', 
                \Illuminate\Validation\Rule::unique('Users')->ignore($this->user()->id)
            ],
        ];
    }}
