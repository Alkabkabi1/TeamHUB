<?php

namespace App\Http\Controllers;

use App\Enums\CertificateField;
use App\Enums\ClubCapability;
use App\Http\Requests\CertificateTemplateRequest;
use App\Models\CertificateTemplate;
use App\Models\Club;
use App\Services\CertificateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class CertificateTemplateController extends Controller
{
    public function __construct(
        private readonly CertificateService $service,
    ) {}

    /**
     * List the club's certificate templates.
     */
    public function index(Request $request, Club $club): InertiaResponse
    {
        $this->authorize(ClubCapability::IssueCertificates->value, $club);

        $templates = $club->certificateTemplates()
            ->with(['media', 'placeholders'])
            ->latest()
            ->get()
            ->map(fn (CertificateTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'status' => $template->status,
                'is_default' => $template->is_default,
                'image_url' => $template->imageUrl(),
                'width' => $template->width,
                'height' => $template->height,
                'fields_count' => $template->placeholders->count(),
                // Fractional (0–1) coordinates so the index preview can overlay
                // each variable field on the template image.
                'fields' => $template->placeholders->map(fn ($placeholder): array => [
                    'text' => $placeholder->static_text ?: __($placeholder->binding->label()),
                    'is_image' => $placeholder->binding->isImage(),
                    'x' => (float) $placeholder->x,
                    'y' => (float) $placeholder->y,
                    'width' => (float) $placeholder->width,
                    'font_size' => (float) $placeholder->font_size,
                    'align' => $placeholder->align,
                    'color' => $placeholder->color,
                    'font_weight' => $placeholder->font_weight,
                ])->all(),
            ]);

        return Inertia::render('clubs/certificate-templates/Index', [
            'club' => $club->only(['id', 'name']),
            'templates' => $templates,
        ]);
    }

    /**
     * Show the designer for a new template.
     */
    public function create(Request $request, Club $club): InertiaResponse
    {
        $this->authorize(ClubCapability::IssueCertificates->value, $club);

        return Inertia::render('clubs/certificate-templates/Editor', [
            'club' => $club->only(['id', 'name']),
            'fieldCatalog' => $this->fieldCatalog(),
            'template' => null,
            'mode' => 'create',
        ]);
    }

    /**
     * Store a new template.
     */
    public function store(CertificateTemplateRequest $request, Club $club): RedirectResponse
    {
        $template = new CertificateTemplate([
            'name' => $request->validated('name'),
            'status' => $request->validated('status') ?? 'draft',
        ]);
        $template->club()->associate($club);
        $template->save();

        $this->applyImageAndFields($request, $template);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('certificate_templates.created'),
        ]);

        return redirect()->route('certificate-templates.index', $club);
    }

    /**
     * Show the designer for an existing template.
     */
    public function edit(Request $request, Club $club, CertificateTemplate $template): InertiaResponse
    {
        $this->authorize(ClubCapability::IssueCertificates->value, $club);
        $this->ensureBelongsToClub($template, $club);

        return Inertia::render('clubs/certificate-templates/Editor', [
            'club' => $club->only(['id', 'name']),
            'fieldCatalog' => $this->fieldCatalog(),
            'template' => $this->templatePayload($template),
            'mode' => 'edit',
        ]);
    }

    /**
     * Update an existing template.
     */
    public function update(CertificateTemplateRequest $request, Club $club, CertificateTemplate $template): RedirectResponse
    {
        $this->ensureBelongsToClub($template, $club);

        $template->update([
            'name' => $request->validated('name'),
            'status' => $request->validated('status') ?? $template->status,
        ]);

        $this->applyImageAndFields($request, $template);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('certificate_templates.updated'),
        ]);

        return redirect()->route('certificate-templates.index', $club);
    }

    /**
     * Delete a template.
     */
    public function destroy(Request $request, Club $club, CertificateTemplate $template): RedirectResponse
    {
        $this->authorize(ClubCapability::IssueCertificates->value, $club);
        $this->ensureBelongsToClub($template, $club);

        $template->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('certificate_templates.deleted'),
        ]);

        return redirect()->route('certificate-templates.index', $club);
    }

    /**
     * Make a template the club's active default, unsetting any previous default.
     */
    public function setDefault(Request $request, Club $club, CertificateTemplate $template): RedirectResponse
    {
        $this->authorize(ClubCapability::IssueCertificates->value, $club);
        $this->ensureBelongsToClub($template, $club);

        DB::transaction(function () use ($club, $template): void {
            $club->certificateTemplates()->where('id', '!=', $template->id)->update(['is_default' => false]);
            $template->update(['is_default' => true, 'status' => 'active']);
        });

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('certificate_templates.default_set'),
        ]);

        return redirect()->route('certificate-templates.index', $club);
    }

    /**
     * Stream a sample PDF render of the template for the designer preview.
     */
    public function preview(Request $request, Club $club, CertificateTemplate $template): Response
    {
        $this->authorize(ClubCapability::IssueCertificates->value, $club);
        $this->ensureBelongsToClub($template, $club);

        return response($this->service->renderPreviewBytes($template), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview.pdf"',
        ]);
    }

    /**
     * Persist the uploaded background image (capturing its natural dimensions)
     * and replace the template's placeholders with the submitted set.
     */
    private function applyImageAndFields(CertificateTemplateRequest $request, CertificateTemplate $template): void
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $dimensions = getimagesize($file->getPathname());

            $template->update([
                'width' => $dimensions ? (int) $dimensions[0] : 0,
                'height' => $dimensions ? (int) $dimensions[1] : 0,
            ]);

            $template->addMedia($file)->toMediaCollection(CertificateTemplate::TEMPLATE_COLLECTION);
        }

        DB::transaction(function () use ($request, $template): void {
            $template->placeholders()->delete();

            foreach ($request->validated('fields', []) as $index => $field) {
                $template->placeholders()->create([
                    'binding' => $field['binding'],
                    'static_text' => $field['static_text'] ?? null,
                    'x' => $field['x'],
                    'y' => $field['y'],
                    'width' => $field['width'],
                    'font_size' => $field['font_size'],
                    'font_family' => $field['font_family'] ?? 'DejaVu Sans',
                    'font_weight' => $field['font_weight'] ?? 'normal',
                    'color' => $field['color'] ?? '#000000',
                    'align' => $field['align'] ?? 'center',
                    'sort' => $index,
                ]);
            }
        });
    }

    /**
     * Serialize a template (with placeholders) for the designer.
     *
     * @return array<string, mixed>
     */
    private function templatePayload(CertificateTemplate $template): array
    {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'status' => $template->status,
            'is_default' => $template->is_default,
            'image_url' => $template->imageUrl(),
            'width' => $template->width,
            'height' => $template->height,
            'fields' => $template->placeholders->map(fn ($placeholder): array => [
                'binding' => $placeholder->binding->value,
                'static_text' => $placeholder->static_text,
                'x' => (float) $placeholder->x,
                'y' => (float) $placeholder->y,
                'width' => (float) $placeholder->width,
                'font_size' => (float) $placeholder->font_size,
                'font_family' => $placeholder->font_family,
                'font_weight' => $placeholder->font_weight,
                'color' => $placeholder->color,
                'align' => $placeholder->align,
            ])->all(),
        ];
    }

    /**
     * The catalog of bindable fields shown in the designer.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fieldCatalog(): array
    {
        return array_map(fn (CertificateField $field): array => [
            'value' => $field->value,
            'label' => __($field->label()),
            'is_image' => $field->isImage(),
            'is_static' => $field->isStatic(),
        ], CertificateField::cases());
    }

    /**
     * Guard that the template belongs to the club in the route.
     */
    private function ensureBelongsToClub(CertificateTemplate $template, Club $club): void
    {
        if ($template->club_id !== $club->id) {
            abort(404);
        }
    }
}
