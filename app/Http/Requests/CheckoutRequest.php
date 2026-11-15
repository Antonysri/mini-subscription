<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
// 🌟 Rule class: Advanced DB existence & conditional check panna import panrom
use Illuminate\Validation\Rule;

class CheckoutRequest extends FormRequest
{
    /**
     * AUTHORIZATION CHECK:
     * ❓ INTHA FUNCTION ENNA PANNUTHU?
     * Intha request-ah anupa user-ku thaguthi/permission irukka nu check pannum.
     * Login panna ella user-um checkout start pannalaam ngrathaala 'true' nu tharrom.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * VALIDATION RULES:
     * ❓ ENNA RULES CHECK PANROM?
     * 
     * 1. 'required' -> Payload-la 'plan_id' kandippa irukanum.
     * 2. 'integer'  -> 1, 2, 3 mathiri valid integer ID-ah irukanum ('abc' anupina reject aagum).
     * 3. Rule::exists('plans', 'id')->where('is_active', true):
     *    - 'plans' table-la intha ID irukanum.
     *    - Koodave antha plan 'is_active = true' nu active-ah iruntha mattum thaan pass aaganum!
     *    - Deactivated / Inactive plans-ah hacker-aaleyum checkout panna mudiyathu!
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'integer',
                Rule::exists('plans', 'id')->where(function ($query) {
                    $query->where('is_active', true);
                }),
            ],
        ];
    }

    /**
     * CUSTOM ERROR MESSAGES:
     * Validation fail aana frontend-ku neat-ana error message anupa:
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'The plan_id field is required.',
            'plan_id.integer'  => 'The plan_id must be a valid integer.',
            'plan_id.exists'   => 'The selected plan is invalid or currently inactive.',
        ];
    }
}