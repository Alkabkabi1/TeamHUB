<?php

namespace App\Http\Requests;

use App\Enums\CertificateField;
use App\Enums\ClubCapability;
use App\Models\CertificateTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CertificateTemplateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(ClubCapability::IssueCertificates->value, $this->route('club')) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['draft', 'active'])],
            'image' => [Rule::requiredIf(! $this->hasExistingImage()), 'image', 'mimes:png,jpg,jpeg', 'max:10240'],
            'fields' => ['nullable', 'array'],
            'fields.*.binding' => ['required', Rule::in(CertificateField::values())],
            'fields.*.static_text' => ['nullable', 'string', 'max:500', 'required_if:fields.*.binding,'.CertificateField::StaticText->value],
            'fields.*.x' => ['required', 'numeric', 'between:0,1'],
            'fields.*.y' => ['required', 'numeric', 'between:0,1'],
            'fields.*.width' => ['required', 'numeric', 'between:0.01,1'],
            'fields.*.font_size' => ['required', 'numeric', 'between:0.005,0.5'],
            'fields.*.font_family' => ['nullable', 'string', 'max:100'],
            'fields.*.font_weight' => ['nullable', Rule::in(['normal', 'bold'])],
            'fields.*.color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'fields.*.align' => ['nullable', Rule::in(['left', 'center', 'right'])],
        ];
    }

    /**
     * Whether the edited template already has a background image, making a new
     * upload optional on update.
     */
    private function hasExistingImage(): bool
    {
        $template = $this->route('template');

        return $template instanceof CertificateTemplate
            && $template->imagePath() !== null;
    }
}
