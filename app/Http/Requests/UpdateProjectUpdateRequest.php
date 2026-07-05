<?php

namespace App\Http\Requests;

use App\Enums\ProjectCapability;
use App\Models\Project;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProjectUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Project|null $project */
        $project = $this->route('project') ?? $this->route('project');

        return $project !== null
            && ($this->user()?->can(ProjectCapability::ManageUpdates->value, $project) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:50000'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*' => ['image', 'mimes:jpeg,jpg,png,webp', 'max:10240'],
            'removed_media' => ['nullable', 'array'],
            'removed_media.*' => ['integer'],
        ];
    }
}
