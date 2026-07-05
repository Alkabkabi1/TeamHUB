<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->route('project');

        return $project instanceof Project
            && ($this->user()?->can('create', [Task::class, $project]) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', Rule::in($this->approvedProjectMemberIds())],
            'priority' => ['nullable', 'string', Rule::in(TaskPriority::values())],
            'status' => ['nullable', 'string', Rule::in([TaskStatus::Todo->value, TaskStatus::InProgress->value])],
            'due_at' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => __('tasks.validation.title.required'),
            'assigned_to.in' => __('tasks.validation.assigned_to.project_member'),
            'priority.in' => __('tasks.validation.priority.in'),
            'status.in' => __('tasks.validation.status.in'),
            'due_at.date' => __('tasks.validation.due_at.date'),
        ];
    }

    /**
     * @return array<int, int>
     */
    private function approvedProjectMemberIds(): array
    {
        $project = $this->route('project');

        if (! $project instanceof Project) {
            return [];
        }

        return $project->memberships()
            ->where('status', 'approved')
            ->pluck('user_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }
}
