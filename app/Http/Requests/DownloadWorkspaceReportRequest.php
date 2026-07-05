<?php

namespace App\Http\Requests;

use App\Enums\WorkspaceCapability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DownloadWorkspaceReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $workspace = $this->route('workspace');

        return $this->user() !== null && $this->user()->can(WorkspaceCapability::ViewReports->value, $workspace);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'locale' => ['nullable', 'string', 'in:ar,en'],
        ];
    }

    public function reportLocale(): string
    {
        $locale = $this->validated('locale') ?? app()->getLocale();

        return in_array($locale, ['ar', 'en'], true) ? $locale : 'ar';
    }
}
