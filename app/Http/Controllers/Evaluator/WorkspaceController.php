<?php

namespace App\Http\Controllers\Evaluator;

use App\Http\Controllers\Controller;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Paper;
use App\Models\Track;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

/**
 * The evaluator's single-page workspace: every track, every paper, and this
 * evaluator's own ratings are loaded once. Switching track or paper happens
 * client-side; ?track= and ?paper= make any position deep-linkable.
 */
class WorkspaceController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $userId = $user->id;

        // An evaluator only ever sees the one track they are assigned to.
        $tracks = Track::with('papers')->whereKey($user->track_id)->orderBy('number')->get();

        $evaluations = Evaluation::with('scores')
            ->where('user_id', $userId)
            ->get()
            ->keyBy('paper_id');

        $trackData = $tracks->map(function (Track $track) use ($evaluations) {
            $papers = $track->papers->map(function (Paper $paper) use ($evaluations) {
                $evaluation = $evaluations->get($paper->id);
                $submitted = (bool) $evaluation?->submitted_at;

                return [
                    'id' => $paper->id,
                    'paper_no' => $paper->paper_no,
                    'title' => $paper->title,
                    'researcher' => $paper->researcher,
                    'affiliation' => $paper->affiliation,
                    'presentation_order' => $paper->presentation_order,
                    'submitted' => $submitted,
                    'total' => $submitted ? (float) $evaluation->total : null,
                    'submitted_at' => $evaluation?->submitted_at?->toDateTimeString(),
                    'has_manuscript' => $paper->hasManuscript(),
                    'manuscript_url' => $paper->manuscript_url,
                    'evaluation' => $submitted ? [
                        'scores' => $evaluation->scores
                            ->mapWithKeys(fn ($s) => [$s->criterion_id => (float) $s->score])
                            ->all(),
                        'comments' => $evaluation->comments,
                    ] : null,
                ];
            })->values();

            return [
                'id' => $track->id,
                'number' => $track->number,
                'name' => $track->name,
                'label' => $track->label,
                'venue' => $track->venue,
                'is_locked' => $track->is_locked,
                'papers' => $papers,
                'papers_count' => $papers->count(),
                'evaluated_count' => $papers->where('submitted', true)->count(),
            ];
        })->values();

        return Inertia::render('Evaluator/Workspace', [
            'tracks' => $trackData,
            'criteria' => Criterion::ordered()->get()->map(fn (Criterion $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'description' => $c->description,
                'weight' => $c->weight,
            ])->values(),
            'selected' => $this->resolveSelection(
                $trackData,
                $request->integer('track'),
                $request->integer('paper')
            ),
            'totals' => [
                'papers' => $trackData->sum('papers_count'),
                'evaluated' => $trackData->sum('evaluated_count'),
            ],
        ]);
    }

    /**
     * Decide which track and paper to open. Explicit ?paper wins, then ?track
     * (first pending paper in it), otherwise the first track with pending work.
     */
    protected function resolveSelection(Collection $tracks, int $trackId, int $paperId): array
    {
        if ($paperId) {
            foreach ($tracks as $track) {
                if ($track['papers']->contains('id', $paperId)) {
                    return ['track_id' => $track['id'], 'paper_id' => $paperId];
                }
            }
        }

        $track = $trackId ? $tracks->firstWhere('id', $trackId) : null;

        if (! $track) {
            $track = $tracks->first(fn ($t) => $t['papers']->contains('submitted', false))
                ?? $tracks->first(fn ($t) => $t['papers_count'] > 0)
                ?? $tracks->first();
        }

        if (! $track) {
            return ['track_id' => null, 'paper_id' => null];
        }

        $paper = $track['papers']->firstWhere('submitted', false) ?? $track['papers']->first();

        return ['track_id' => $track['id'], 'paper_id' => $paper['id'] ?? null];
    }
}