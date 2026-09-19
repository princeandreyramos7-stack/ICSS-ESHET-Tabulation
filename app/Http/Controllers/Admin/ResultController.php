<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paper;
use App\Models\Track;
use App\Services\ResultPdf;
use App\Services\ResultService;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class ResultController extends Controller
{
    /**
     * Printable per-track result sheet.
     */
    public function track(Track $track, ResultService $results): Response
    {
        return Inertia::render('Admin/Results/Track', [
            'result' => $results->forTrack($track),
        ]);
    }

    /**
     * Download per-track result sheet as PDF.
     */
    public function trackPdf(Track $track, ResultService $results, ResultPdf $pdf): HttpResponse
    {
        $result = $results->forTrack($track);

        // Wide panels get one column per evaluator; switch to landscape so names stay readable.
        $orientation = count($result['evaluators']) > 4 ? 'landscape' : 'portrait';

        return $pdf->download(
            'pdf.results.track',
            ['result' => $result],
            'Track-' . $result['track']['number'] . '-Results-' . now()->format('Y-m-d') . '.pdf',
            $orientation
        );
    }

    /**
     * Printable summary of every track plus a cross-track leaderboard.
     */
    public function overall(ResultService $results): Response
    {
        return Inertia::render('Admin/Results/Overall', [
            'result' => $results->overall(),
        ]);
    }

    /**
     * Download overall results as PDF.
     */
    public function overallPdf(ResultService $results, ResultPdf $pdf): HttpResponse
    {
        return $pdf->download(
            'pdf.results.overall',
            ['result' => $results->overall()],
            'Overall-Results-' . now()->format('Y-m-d') . '.pdf'
        );
    }

    /**
     * Printable per-paper breakdown (each evaluator's criterion ratings and comments).
     */
    public function paper(Paper $paper, ResultService $results): Response
    {
        $paper->load('track');

        return Inertia::render('Admin/Results/Paper', [
            'result' => $results->forPaper($paper),
            'track' => [
                'id' => $paper->track->id,
                'number' => $paper->track->number,
                'name' => $paper->track->name,
                'label' => $paper->track->label,
            ],
        ]);
    }

    /**
     * Download per-paper breakdown as PDF.
     */
    public function paperPdf(Paper $paper, ResultService $results, ResultPdf $pdf): HttpResponse
    {
        $paper->load('track');

        $result = $results->forPaper($paper);
        $track = [
            'id' => $paper->track->id,
            'number' => $paper->track->number,
            'name' => $paper->track->name,
            'label' => $paper->track->label,
        ];

        // Rank comes from the track sheet so the PDF agrees with what admins see on screen.
        $rank = collect($results->forTrack($paper->track)['papers'])
            ->firstWhere('id', $paper->id)['rank'] ?? null;

        return $pdf->download(
            'pdf.results.paper',
            ['result' => $result, 'track' => $track, 'rank' => $rank],
            'Paper-' . $result['paper']['paper_no'] . '-Breakdown-' . now()->format('Y-m-d') . '.pdf'
        );
    }
}
