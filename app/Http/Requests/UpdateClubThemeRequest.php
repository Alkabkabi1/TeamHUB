<?php

namespace App\Http\Requests;

use App\Enums\ClubCapability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateClubThemeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(ClubCapability::ManageClub->value, $this->route('club'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'theme' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:png,jpeg,jpg', 'max:5120'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('validation.required', ['attribute' => __('app.name')]),
            'theme.required' => __('theme.validation.theme_required'),
            'theme.regex' => __('theme.validation.theme_hex'),
            'logo.image' => __('theme.validation.logo_image'),
            'logo.mimes' => __('theme.validation.logo_mimes'),
            'logo.max' => __('theme.validation.logo_max'),
        ];
    }
}
