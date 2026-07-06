<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceStatus;
use App\Models\Workspace;
use App\Models\WorkspaceMembership;
use App\Models\WorkspaceMembershipRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkspaceMembershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isMember();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
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
            'phone.required' => __('join.validation.phone.required'),
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
            /** @var Workspace $workspace */
            $workspace = $this->route('workspace') ?? $this->route('workspace');
            $user = $this->user();

            if ($workspace->status !== WorkspaceStatus::Active) {
                $validator->errors()->add('workspace', __('join.validation.workspace.inactive'));
            }

            if (WorkspaceMembership::query()->where('user_id', $user?->id)->where('workspace_id', $workspace->id)->exists()) {
                $validator->errors()->add('workspace', __('join.validation.workspace.already_member'));
            }

            if (WorkspaceMembershipRequest::query()
                ->where('user_id', $user?->id)
                ->where('workspace_id', $workspace->id)
                ->where('status', 'pending')
                ->exists()) {
                $validator->errors()->add('workspace', __('join.validation.workspace.pending_application'));
            }
        });
    }
}
