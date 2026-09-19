<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paper;
use App\Models\Track;
use App\Services\ResultService;
use Barryvdh\DomPDF\Facade\Pdf;
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
    public function trackPdf(Track $track, ResultService $results): HttpResponse
    {
        $result = $results->forTrack($track);
        
        $pdf = Pdf::loadView('pdf.results.track', ['result' => $result])
            ->setPaper('a4', 'landscape')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        $filename = 'Track-' . $result['track']['number'] . '-Results-' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
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
    public function overallPdf(ResultService $results): HttpResponse
    {
        $result = $results->overall();
        
        $pdf = Pdf::loadView('pdf.results.overall', ['result' => $result])
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        $filename = 'Overall-Results-' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
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
    public function paperPdf(Paper $paper, ResultService $results): HttpResponse
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

        $pdf = Pdf::loadView('pdf.results.paper', ['result' => $result, 'track' => $track, 'rank' => $rank])
            ->setPaper('a4', 'portrait')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->setOption('margin-left', 10)
            ->setOption('margin-right', 10);

        $filename = 'Paper-' . $result['paper']['paper_no'] . '-Breakdown-' . now()->format('Y-m-d') . '.pdf';
        
        return $pdf->download($filename);
    }
}
