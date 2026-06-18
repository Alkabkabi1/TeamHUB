<?php

namespace App\Http\Requests;

use App\Enums\CommitteeCapability;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class DownloadCommitteeReportRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $committee = $this->route('committee');

        return $this->user() !== null && $this->user()->can(CommitteeCapability::ViewReports->value, $committee);
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
