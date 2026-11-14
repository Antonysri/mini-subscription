<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * AUTHORIZATION CHECK:
     * Intha request-ah anupa user-ku permission iruka nu paaka.
     * Registration open-to-public (yar vena register pannalam) ngrathala 'true' nu tharrom.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * VALIDATION RULES:
     * Registration form-la vara vendiya fields-oda rules.
     */
    public function rules(): array
    {
        return [
            // 1. Name: Kandippa irukanum, string-ah irukanum, max 255 chars
            'name' => ['required', 'string', 'max:255'],

            // 2. Email: Required, proper email format, max 255 chars,
            // 'unique:users,email' -> Users table-la intha email already iruntha reject pannidum!
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],

            // 3. Password: Required, minimum 6 characters irukanum.
            // 'confirmed' -> Client 'password' kooda 'password_confirmation' nu innoru field anupanum.
            // Rendum match aana mattum thaan pass aagum!
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ];
    }
}