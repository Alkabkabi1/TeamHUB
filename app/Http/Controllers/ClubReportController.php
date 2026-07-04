<?php

namespace App\Http\Controllers;

use App\Http\Requests\DownloadClubReportRequest;
use App\Models\Club;
use App\Services\ClubSupervisorReportService;
use App\Support\ArabicText;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ClubReportController extends Controller
{
    public function __construct(
        private readonly ClubSupervisorReportService $reports,
    ) {}

    public function members(DownloadClubReportRequest $request, Club $club): Response
    {
        return $this->download(
            $request,
            $club,
            'members',
            fn (string $locale) => $this->reports->membersReport($club, $locale, $request->user()),
        );
    }

    /**
     * @param  callable(string): array<string, mixed>  $dataResolver
     */
    private function download(
        DownloadClubReportRequest $request,
        Club $club,
        string $reportKey,
        callable $dataResolver,
    ): Response {
        $locale = $request->reportLocale();
        app()->setLocale($locale);

        $data = $dataResolver($locale);
        $filename = "{$reportKey}-club-{$club->id}-{$locale}.pdf";

        $html = ArabicText::shapeHtml(view("reports.{$reportKey}.{$locale}", $data)->render());

        return Pdf::loadHTML($html)->download($filename);
    }
}
