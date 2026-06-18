<?php

namespace App\Http\Requests;

use App\Enums\ClubCapability;
use App\Models\Club;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreManualCertificateRequest extends FormRequest
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
        /** @var Club $club */
        $club = $this->route('club');

        return [
            'template_id' => [
                'required',
                'integer',
                Rule::exists('certificate_templates', 'id')
                    ->where('club_id', $club->id)
                    ->where('status', 'active'),
            ],
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'event_id' => [
                'nullable',
                'integer',
                Rule::exists('events', 'id')->where('club_id', $club->id),
            ],
        ];
    }
}
