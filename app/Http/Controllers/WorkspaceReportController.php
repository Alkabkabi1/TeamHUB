<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadWorkspaceReportRequest;
use App\Models\Workspace;
use App\Services\WorkspaceMemberReportService;
use App\Support\ArabicText;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class WorkspaceReportController extends Controller
{
    public function __construct(
        private readonly WorkspaceMemberReportService $reports,
    ) {}

    public function members(DownloadWorkspaceReportRequest $request, Workspace $workspace): Response
    {
        return $this->download(
            $request,
            $workspace,
            'members',
            fn (string $locale) => $this->reports->membersReport($workspace, $locale, $request->user()),
        );
    }

    /**
     * @param  callable(string): array<string, mixed>  $dataResolver
     */
    private function download(
        DownloadWorkspaceReportRequest $request,
        Workspace $workspace,
        string $reportKey,
        callable $dataResolver,
    ): Response {
        $locale = $request->reportLocale();
        app()->setLocale($locale);

        $data = $dataResolver($locale);
        $filename = "{$reportKey}-club-{$workspace->id}-{$locale}.pdf";

        $html = ArabicText::shapeHtml(view("reports.{$reportKey}.{$locale}", $data)->render());

        return Pdf::loadHTML($html)->download($filename);
    }
}
