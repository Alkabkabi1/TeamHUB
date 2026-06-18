<?php

namespace App\Http\Requests;

use App\Concerns\AuthorizesClubOrCommittee;
use App\Enums\ClubCapability;
use App\Enums\CommitteeCapability;
use App\Enums\EventStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    use AuthorizesClubOrCommittee;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->authorizeClubOrCommittee(ClubCapability::ManageEvents, CommitteeCapability::ManageEvents);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', Rule::in(EventStatus::values())],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'removed_media' => ['nullable', 'array'],
            'removed_media.*' => ['integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('events.validation.title.required'),
            'title.max' => __('events.validation.title.max'),
            'starts_at.required' => __('events.validation.starts_at.required'),
            'starts_at.date' => __('events.validation.starts_at.date'),
            'ends_at.required' => __('events.validation.ends_at.required'),
            'ends_at.date' => __('events.validation.ends_at.date'),
            'ends_at.after_or_equal' => __('events.validation.ends_at.after_or_equal'),
            'capacity.integer' => __('events.validation.capacity.integer'),
            'capacity.min' => __('events.validation.capacity.min'),
            'status.in' => __('events.validation.status.in'),
            'images.max' => __('events.validation.images.max'),
            'images.*.image' => __('events.validation.images.image'),
            'images.*.mimes' => __('events.validation.images.mimes'),
            'images.*.max' => __('events.validation.images.size'),
        ];
    }
}
