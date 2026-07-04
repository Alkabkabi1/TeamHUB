<?php

namespace App\Http\Requests;

use App\Enums\CommitteeCapability;
use App\Models\Committee;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Committee|null $committee */
        $committee = $this->route('committee');

        return $committee !== null
            && $this->user()?->can(CommitteeCapability::ManageNews->value, $committee) === true;
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
