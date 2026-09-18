<?php

namespace App\Services;

use App\Models\Criterion;
use App\Models\Paper;
use App\Models\Track;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Computes tabulated results. All arithmetic lives here so the
 * admin result sheets, dashboards and tests share one source of truth.
 */
class ResultService
{
    /**
     * Result sheet for one track:
     * rows = papers, columns = every evaluator's total, plus average and rank.
     */
    public function forTrack(Track $track): array
    {
        $evaluators = User::evaluators()->get(['id', 'name']);

        $papers = $track->papers()
            ->with(['evaluations' => fn ($q) => $q->whereNotNull('submitted_at')])
            ->get();

        $rows = $papers->map(function (Paper $paper) use ($evaluators) {
            $byEvaluator = $paper->evaluations->keyBy('user_id');

            $totals = $evaluators->mapWithKeys(function (User $evaluator) use ($byEvaluator) {
                $evaluation = $byEvaluator->get($evaluator->id);

                return [$evaluator->id => $evaluation ? (float) $evaluation->total : null];
            });

            $submitted = $totals->filter(fn ($t) => $t !== null);
            $average = $submitted->isNotEmpty()
                ? round($submitted->sum() / $submitted->count(), 2)
                : null;

            return [
                'id' => $paper->id,
                'paper_no' => $paper->paper_no,
                'title' => $paper->title,
                'researcher' => $paper->researcher,
                'affiliation' => $paper->affiliation,
                'presentation_order' => $paper->presentation_order,
                'totals' => $totals->all(),
                'evaluations_count' => $submitted->count(),
                'average' => $average,
                'rank' => null,
            ];
        });

        $ranked = $this->assignRanks($rows);

        return [
            'track' => [
                'id' => $track->id,
                'number' => $track->number,
                'name' => $track->name,
                'label' => $track->label,
                'is_locked' => $track->is_locked,
            ],
            'evaluators' => $evaluators->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])->values()->all(),
            'papers' => $ranked->values()->all(),
        ];
    }

    /**
     * Detailed breakdown for one paper: each evaluator's score per criterion,
     * total and comments.
     */
    public function forPaper(Paper $paper): array
    {
        $criteria = Criterion::ordered()->get();
        $evaluators = User::evaluators()->get(['id', 'name']);

        $evaluations = $paper->evaluations()
            ->whereNotNull('submitted_at')
            ->with('scores')
            ->get()
            ->keyBy('user_id');

        $rows = $evaluators->map(function (User $evaluator) use ($evaluations, $criteria) {
            $evaluation = $evaluations->get($evaluator->id);
            $scores = $evaluation ? $evaluation->scores->keyBy('criterion_id') : collect();

            return [
                'evaluator_id' => $evaluator->id,
                'evaluator_name' => $evaluator->name,
                'submitted' => (bool) $evaluation,
                'submitted_at' => $evaluation?->submitted_at?->toDateTimeString(),
                'scores' => $criteria->mapWithKeys(function (Criterion $c) use ($scores) {
                    $s = $scores->get($c->id);

                    return [$c->id => $s ? (float) $s->score : null];
                })->all(),
                'total' => $evaluation ? (float) $evaluation->total : null,
                'comments' => $evaluation?->comments,
            ];
        });

        $submittedTotals = $rows->filter(fn ($r) => $r['submitted'])->pluck('total');
        $average = $submittedTotals->isNotEmpty()
            ? round($submittedTotals->sum() / $submittedTotals->count(), 2)
            : null;

        // Per-criterion averages across the evaluators who submitted.
        $criterionAverages = $criteria->mapWithKeys(function (Criterion $c) use ($rows) {
            $values = $rows->filter(fn ($r) => $r['submitted'])
                ->map(fn ($r) => $r['scores'][$c->id] ?? null)
                ->filter(fn ($v) => $v !== null);

            return [$c->id => $values->isNotEmpty() ? round($values->sum() / $values->count(), 2) : null];
        });

        return [
            'paper' => [
                'id' => $paper->id,
                'paper_no' => $paper->paper_no,
                'title' => $paper->title,
                'researcher' => $paper->researcher,
                'affiliation' => $paper->affiliation,
            ],
            'criteria' => $criteria->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'weight' => $c->weight,
            ])->values()->all(),
            'evaluations' => $rows->values()->all(),
            'criterion_averages' => $criterionAverages->all(),
            'average' => $average,
            'evaluations_count' => $submittedTotals->count(),
        ];
    }

    /**
     * Standard competition ranking (1, 1, 3) by average, highest first.
     * Papers with no submitted evaluation are placed last and left unranked.
     */
    /**
     * The one ordering rule used everywhere: higher average first, unevaluated
     * papers last, ties broken by natural paper-number order (T1-9 before T1-10).
     */
    public static function comparePapers(array $a, array $b): int
    {
        if ($a['average'] === null && $b['average'] === null) {
            return strnatcasecmp($a['paper_no'], $b['paper_no']);
        }
        if ($a['average'] === null) {
            return 1;
        }
        if ($b['average'] === null) {
            return -1;
        }
        if ($b['average'] !== $a['average']) {
            return $b['average'] <=> $a['average'];
        }

        return strnatcasecmp($a['paper_no'], $b['paper_no']);
    }

    /**
     * Competition ranks (1, 1, 3) over rows already carrying an `average`.
     * Rows with a null average are placed last and receive no rank.
     */
    public static function rankRows(Collection $rows, string $rankKey = 'rank'): Collection
    {
        $sorted = $rows->sort([self::class, 'comparePapers'])->values();

        $rank = 0;
        $position = 0;
        $previous = null;

        return $sorted->map(function ($row) use (&$rank, &$position, &$previous, $rankKey) {
            $position++;
            if ($row['average'] === null) {
                $row[$rankKey] = null;

                return $row;
            }
            if ($previous === null || $row['average'] < $previous) {
                $rank = $position;
            }
            $previous = $row['average'];
            $row[$rankKey] = $rank;

            return $row;
        });
    }

    protected function assignRanks(Collection $rows): Collection
    {
        return self::rankRows($rows);
    }

    /**
     * Progress numbers used by dashboards.
     */
    public function progress(): array
    {
        $evaluatorCount = User::evaluators()->count();

        $tracks = Track::withCount('papers')->orderBy('number')->get();

        $submittedByTrack = Paper::query()
            ->join('evaluations', 'evaluations.paper_id', '=', 'papers.id')
            ->whereNotNull('evaluations.submitted_at')
            ->selectRaw('papers.track_id, COUNT(*) as submitted')
            ->groupBy('papers.track_id')
            ->pluck('submitted', 'track_id');

        $trackRows = $tracks->map(function (Track $track) use ($evaluatorCount, $submittedByTrack) {
            $expected = $track->papers_count * $evaluatorCount;
            $submitted = (int) ($submittedByTrack[$track->id] ?? 0);

            return [
                'id' => $track->id,
                'number' => $track->number,
                'name' => $track->name,
                'label' => $track->label,
                'is_locked' => $track->is_locked,
                'papers_count' => $track->papers_count,
                'expected' => $expected,
                'submitted' => $submitted,
                'percent' => $expected > 0 ? (int) round($submitted / $expected * 100) : 0,
            ];
        });

        return [
            'papers' => $tracks->sum('papers_count'),
            'evaluators' => $evaluatorCount,
            'expected' => $trackRows->sum('expected'),
            'submitted' => $trackRows->sum('submitted'),
            'tracks' => $trackRows->values()->all(),
        ];
    }
    /**
     * Every track's ranking on one sheet, plus a cross-track leaderboard.
     */
    public function overall(int $leaderboardSize = 10): array
    {
        $tracks = Track::orderBy('number')->get()->map(function (Track $track) {
            $result = $this->forTrack($track);

            return [
                'track' => $result['track'],
                'evaluators_count' => count($result['evaluators']),
                'papers' => array_map(fn ($p) => [
                    'id' => $p['id'],
                    'paper_no' => $p['paper_no'],
                    'title' => $p['title'],
                    'researcher' => $p['researcher'],
                    'average' => $p['average'],
                    'evaluations_count' => $p['evaluations_count'],
                    'rank' => $p['rank'],
                ], $result['papers']),
            ];
        })->values();

        $leaderboard = $tracks->flatMap(function ($t) {
            return collect($t['papers'])
                ->filter(fn ($p) => $p['average'] !== null)
                ->map(fn ($p) => $p + ['track_number' => $t['track']['number'], 'track_name' => $t['track']['name']]);
        });

        // Same comparator and competition ranking as within a track, applied across all tracks.
        $leaderboard = self::rankRows($leaderboard, 'overall_rank')->take($leaderboardSize)->values();

        return [
            'tracks' => $tracks->all(),
            'leaderboard' => $leaderboard->all(),
            'evaluators' => User::evaluators()->get(['id', 'name'])->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])->values()->all(),
            'all_locked' => $tracks->every(fn ($t) => $t['track']['is_locked']),
        ];
    }
}
