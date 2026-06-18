<?php

namespace App\Http\Requests;

use App\Enums\ClubCapability;
use App\Models\Club;
use App\Models\ClubMembership;
use App\Models\Event;
use App\Models\EventAttendance;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVolunteerHourRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Club $club */
        $club = $this->route('club');

        return $this->user() !== null && $this->user()->can(ClubCapability::ManageVolunteerHours->value, $club);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'event_id' => ['nullable', 'integer', Rule::exists('events', 'id')],
            'hours' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * Normalize an empty event selection to null so the optional event rule
     * applies instead of failing the integer check on an empty string.
     */
    protected function prepareForValidation(): void
    {
        if ($this->input('event_id') === '') {
            $this->merge(['event_id' => null]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'user_id.required' => __('volunteer_hours.validation.user_id.required'),
            'user_id.exists' => __('volunteer_hours.validation.user_id.exists'),
            'user_id.not_member' => __('volunteer_hours.validation.user_id.not_member'),
            'event_id.exists' => __('volunteer_hours.validation.event_id.exists'),
            'hours.required' => __('volunteer_hours.validation.hours.required'),
            'hours.numeric' => __('volunteer_hours.validation.hours.numeric'),
            'hours.min' => __('volunteer_hours.validation.hours.min'),
            'hours.max' => __('volunteer_hours.validation.hours.max'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            /** @var Club $club */
            $club = $this->route('club');
            $userId = (int) $this->input('user_id');
            $eventId = $this->input('event_id');

            // General hours (no linked event): only require an approved
            // membership in the club so credit is scoped to the right place.
            if ($eventId === null) {
                $isMember = ClubMembership::query()
                    ->where('club_id', $club->id)
                    ->where('user_id', $userId)
                    ->where('status', 'approved')
                    ->exists();

                if (! $isMember) {
                    $validator->errors()->add('user_id', __('volunteer_hours.validation.user_id.not_member'));
                }

                return;
            }

            $event = Event::query()->find((int) $eventId);

            if ($event === null) {
                return;
            }

            if ($event->club_id !== $club->id) {
                $validator->errors()->add('event_id', __('volunteer_hours.validation.event_id.wrong_club'));
            }

            if ($event->starts_at !== null && $event->starts_at->isFuture()) {
                $validator->errors()->add('event_id', __('volunteer_hours.validation.event_id.not_finished'));
            }

            $hasEligibleAttendance = EventAttendance::query()
                ->where('user_id', $userId)
                ->where('event_id', $eventId)
                ->whereIn('status', ['checked_in', 'approved'])
                ->exists();

            if (! $hasEligibleAttendance) {
                $validator->errors()->add('user_id', __('volunteer_hours.validation.user_id.no_attendance'));
            }
        });
    }
}
