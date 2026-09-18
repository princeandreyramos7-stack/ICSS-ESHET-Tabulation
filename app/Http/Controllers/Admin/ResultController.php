<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Paper;
use App\Models\Track;
use App\Services\ResultService;
use Inertia\Inertia;
use Inertia\Response;

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
     * Printable summary of every track plus a cross-track leaderboard.
     */
    public function overall(ResultService $results): Response
    {
        return Inertia::render('Admin/Results/Overall', [
            'result' => $results->overall(),
        ]);
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
}
