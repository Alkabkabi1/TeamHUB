<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadProjectReportRequest;
use App\Models\Project;
use App\Models\Workspace;
use App\Services\ProjectMemberReportService;
use App\Support\ArabicText;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ProjectReportController extends Controller
{
    public function __construct(
        private readonly ProjectMemberReportService $reports,
    ) {}

    public function members(DownloadProjectReportRequest $request, Workspace $workspace, Project $project): Response
    {
        return $this->download(
            $request,
            $project,
            'members',
            fn (string $locale) => $this->reports->membersReport($project, $locale, $request->user()?->name),
        );
    }

    /**
     * @param  callable(string): array<string, mixed>  $dataResolver
     */
    private function download(
        DownloadProjectReportRequest $request,
        Project $project,
        string $reportKey,
        callable $dataResolver,
    ): Response {
        $locale = $request->reportLocale();
        app()->setLocale($locale);

        $data = $dataResolver($locale);
        $filename = "{$reportKey}-project-{$project->id}-{$locale}.pdf";

        $html = ArabicText::shapeHtml(view("reports.{$reportKey}.{$locale}", $data)->render());

        return Pdf::loadHTML($html)->download($filename);
    }
}
