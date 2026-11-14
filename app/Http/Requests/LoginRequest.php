<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * AUTHORIZATION CHECK:
     * Login request yar vena anupalam ngrathala 'true' tharrom.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * VALIDATION RULES:
     * Login panna Email and Password mattum pothum.
     */
    public function rules(): array
    {
        return [
            // 1. Email: Kandippa irukanum, proper email-ah irukanum
            'email' => ['required', 'string', 'email'],

            // 2. Password: Plain string-ah kandippa anupanum
            'password' => ['required', 'string'],
        ];
    }
}