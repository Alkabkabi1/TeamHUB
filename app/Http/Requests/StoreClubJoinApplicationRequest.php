<?php

namespace App\Http\Requests;

use App\Enums\ClubStatus;
use App\Models\Club;
use App\Models\ClubJoinApplication;
use App\Models\ClubMembership;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreClubJoinApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isStudent();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'university_email' => [
                'required',
                'email',
                'max:255',
            ],
            'phone' => ['required', 'string', 'max:20'],
            'level' => ['required', 'string', 'max:100'],
            'major' => ['required', 'string', 'max:255'],
            'skills' => ['required', 'string', 'max:2000'],
            'weekly_hours' => ['required', 'integer', 'min:1', 'max:40'],
            'tools' => ['required', 'string', 'max:2000'],
            'motivation' => ['required', 'string', 'max:5000'],
            'contribution' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'full_name.required' => __('join.validation.full_name.required'),
            'university_email.required' => __('join.validation.university_email.required'),
            'university_email.email' => __('join.validation.university_email.email'),
            'phone.required' => __('join.validation.phone.required'),
            'level.required' => __('join.validation.level.required'),
            'major.required' => __('join.validation.major.required'),
            'skills.required' => __('join.validation.skills.required'),
            'weekly_hours.required' => __('join.validation.weekly_hours.required'),
            'tools.required' => __('join.validation.tools.required'),
            'motivation.required' => __('join.validation.motivation.required'),
            'contribution.required' => __('join.validation.contribution.required'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            /** @var Club $club */
            $club = $this->route('club');
            $user = $this->user();

            if ($club->status !== ClubStatus::Active) {
                $validator->errors()->add('club', __('join.validation.club.inactive'));
            }

            if ($this->input('university_email') !== $user?->email) {
                $validator->errors()->add('university_email', __('join.validation.university_email.mismatch'));
            }

            if (ClubMembership::query()->where('user_id', $user?->id)->where('club_id', $club->id)->exists()) {
                $validator->errors()->add('club', __('join.validation.club.already_member'));
            }

            if (ClubJoinApplication::query()
                ->where('user_id', $user?->id)
                ->where('club_id', $club->id)
                ->where('status', 'pending')
                ->exists()) {
                $validator->errors()->add('club', __('join.validation.club.pending_application'));
            }
        });
    }
}
