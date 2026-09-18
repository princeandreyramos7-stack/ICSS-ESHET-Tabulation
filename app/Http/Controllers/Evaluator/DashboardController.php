<?php

namespace App\Http\Controllers\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Paper;
use App\Models\Track;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Evaluator overview: progress across tracks, the next paper to score,
 * recent submissions, and the rubric at a glance.
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $userId = $user->id;

        // An evaluator only ever sees the one track they are assigned to.
        $tracks = Track::with('papers')->whereKey($user->track_id)->orderBy('number')->get();

        $mine = Evaluation::where('user_id', $userId)
            ->whereNotNull('submitted_at')
            ->get()
            ->keyBy('paper_id');

        $trackRows = $tracks->map(function (Track $track) use ($mine) {
            $papers = $track->papers;
            $evaluated = $papers->filter(fn (Paper $p) => $mine->has($p->id))->count();
            $nextPending = $papers->first(fn (Paper $p) => ! $mine->has($p->id));

            return [
                'id' => $track->id,
                'number' => $track->number,
                'name' => $track->name,
                'is_locked' => $track->is_locked,
                'papers_count' => $papers->count(),
                'evaluated_count' => $evaluated,
                'next_paper_id' => $nextPending?->id,
            ];
        })->values();

        // First pending paper overall (in track order), for the "continue" button.
        $next = $trackRows->first(fn ($t) => $t['next_paper_id'] !== null && ! $t['is_locked']);
        $nextPaper = $next ? Paper::with('track')->find($next['next_paper_id']) : null;

        $recent = Evaluation::with('paper.track')
            ->where('user_id', $userId)
            ->whereNotNull('submitted_at')
            ->orderByDesc('submitted_at')
            ->limit(6)
            ->get()
            ->map(fn (Evaluation $e) => [
                'paper_id' => $e->paper_id,
                'paper_no' => $e->paper->paper_no,
                'title' => $e->paper->title,
                'researcher' => $e->paper->researcher,
                'track_id' => $e->paper->track_id,
                'track_number' => $e->paper->track->number,
                'track_locked' => $e->paper->track->is_locked,
                'total' => (float) $e->total,
                'submitted_at' => $e->submitted_at->toDateTimeString(),
            ])->values();

        $totalPapers = $trackRows->sum('papers_count');
        $evaluated = $trackRows->sum('evaluated_count');

        return Inertia::render('Evaluator/Dashboard', [
            'tracks' => $trackRows,
            'totals' => [
                'papers' => $totalPapers,
                'evaluated' => $evaluated,
                'pending' => $totalPapers - $evaluated,
                'locked_tracks' => $trackRows->where('is_locked', true)->count(),
                'average_given' => $mine->isNotEmpty() ? round($mine->avg('total'), 2) : null,
            ],
            'next_paper' => $nextPaper ? [
                'id' => $nextPaper->id,
                'paper_no' => $nextPaper->paper_no,
                'title' => $nextPaper->title,
                'researcher' => $nextPaper->researcher,
                'track_id' => $nextPaper->track_id,
                'track_number' => $nextPaper->track->number,
                'track_name' => $nextPaper->track->name,
            ] : null,
            'recent' => $recent,
            'criteria' => Criterion::ordered()->get()->map(fn (Criterion $c) => [
                'name' => $c->name,
                'weight' => $c->weight,
            ])->values(),
        ]);
    }
}
