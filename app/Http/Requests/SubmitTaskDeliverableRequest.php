<?php

namespace App\Http\Requests;

use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SubmitTaskDeliverableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $task = $this->route('task');

        return $task instanceof Task
            && ($this->user()?->can('submitDeliverable', $task) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'deliverable_file' => ['nullable', 'file', 'max:10240'],
            'deliverable_url' => ['nullable', 'url', 'max:2048'],
            'deliverable_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'deliverable_file.file' => __('tasks.validation.deliverable.file'),
            'deliverable_file.max' => __('tasks.validation.deliverable.max'),
            'deliverable_url.url' => __('tasks.validation.deliverable.url'),
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (
                ! $this->hasFile('deliverable_file')
                && blank($this->input('deliverable_url'))
                && blank($this->input('deliverable_notes'))
            ) {
                $validator->errors()->add('deliverable_file', __('tasks.validation.deliverable.required'));
            }
        });
    }
}
