<?php

namespace App\Http\Requests;

use App\Concerns\AuthorizesClubOrCommittee;
use App\Enums\ClubCapability;
use App\Enums\CommitteeCapability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    use AuthorizesClubOrCommittee;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->authorizeClubOrCommittee(ClubCapability::ManageNews, CommitteeCapability::ManageNews);
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
            'body' => ['required', 'string'],
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
            'title.required' => __('news.validation.title.required'),
            'body.required' => __('news.validation.body.required'),
            'images.max' => __('news.validation.image.count'),
            'images.*.image' => __('news.validation.image.image'),
            'images.*.mimes' => __('news.validation.image.mimes'),
            'images.*.max' => __('news.validation.image.max'),
        ];
    }
}
