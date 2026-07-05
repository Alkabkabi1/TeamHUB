<?php

namespace App\Http\Requests;

use App\Enums\TaskPriority;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDashboardTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        $project = $this->project();

        return $project instanceof Project
            && ($this->user()?->can('create', [Task::class, $project]) ?? false);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'assigned_to' => ['nullable', 'integer', Rule::in($this->approvedProjectMemberIds())],
            'priority' => ['nullable', 'string', Rule::in(TaskPriority::values())],
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
            'due_at.date' => __('tasks.validation.due_at.date'),
        ];
    }

    public function project(): ?Project
    {
        $projectId = $this->integer('project_id');

        if ($projectId <= 0) {
            return null;
        }

        return Project::query()->find($projectId);
    }

    /**
     * @return array<int, int>
     */
    private function approvedProjectMemberIds(): array
    {
        $project = $this->project();

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
