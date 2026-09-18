<?php

namespace App\Services;

use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\EvaluationScore;
use App\Models\Paper;
use App\Models\Track;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Aggregates for the administrator dashboard. Everything is computed from
 * submitted evaluations only; drafts never exist, so no filtering ambiguity.
 */
class AnalyticsService
{
    public function __construct(protected ResultService $results)
    {
    }

    public function dashboard(): array
    {
        $progress = $this->results->progress();
        $overall = $this->results->overall();

        $evaluations = Evaluation::query()
            ->whereNotNull('submitted_at')
            ->with(['paper:id,track_id,paper_no,title', 'evaluator:id,name'])
            ->orderBy('submitted_at')
            ->get();

        return [
            'progress' => $progress,
            'timeline' => $this->timeline($evaluations),
            'average_by_track' => $this->averageByTrack($overall['tracks']),
            'distribution' => $this->distribution($evaluations),
            'evaluator_activity' => $this->evaluatorActivity($evaluations, $progress['papers']),
            'leaders' => $this->leaders($overall['tracks']),
            'recent' => $evaluations->sortByDesc('submitted_at')->take(8)->map(fn (Evaluation $e) => [
                'id' => $e->id,
                'evaluator' => $e->evaluator?->name ?? 'Unknown',
                'paper_no' => $e->paper?->paper_no,
                'title' => $e->paper?->title,
                'paper_id' => $e->paper_id,
                'total' => (float) $e->total,
                'submitted_at' => $e->submitted_at->toDateTimeString(),
            ])->values()->all(),
            'overall_average' => $evaluations->isNotEmpty() ? round($evaluations->avg('total'), 2) : null,
            'criterion_averages' => $this->criterionAverages(),
            'top_papers' => array_map(fn ($p) => [
                'id' => $p['id'],
                'paper_no' => $p['paper_no'],
                'title' => $p['title'],
                'researcher' => $p['researcher'],
                'track' => "T{$p['track_number']}",
                'average' => $p['average'],
                'evaluations_count' => $p['evaluations_count'],
                'overall_rank' => $p['overall_rank'],
            ], $overall['leaderboard']),
            'track_status' => $this->trackStatus($progress['tracks']),
        ];
    }

    /**
     * Submissions per hour with a running total, so the admin can see the pace.
     */
    protected function timeline(Collection $evaluations): array
    {
        if ($evaluations->isEmpty()) {
            return [];
        }

        $byHour = $evaluations->groupBy(fn (Evaluation $e) => $e->submitted_at->format('Y-m-d H:00'));

        $cumulative = 0;

        return $byHour->map(function (Collection $group, string $hour) use (&$cumulative) {
            $cumulative += $group->count();

            return [
                'hour' => $hour,
                'label' => \Carbon\Carbon::parse($hour)->format('M j, g A'),
                'count' => $group->count(),
                'cumulative' => $cumulative,
            ];
        })->values()->all();
    }

    /**
     * Mean of the paper averages in each track: the same numbers printed on
     * the track result sheets, so the dashboard never disagrees with them.
     */
    protected function averageByTrack(array $overallTracks): array
    {
        return collect($overallTracks)->map(function ($t) {
            $averages = collect($t['papers'])->pluck('average')->filter(fn ($a) => $a !== null);

            return [
                'track' => "T{$t['track']['number']}",
                'name' => $t['track']['name'],
                'average' => $averages->isNotEmpty() ? round($averages->avg(), 2) : null,
                'count' => $averages->count(),
            ];
        })->values()->all();
    }

    /**
     * Histogram of evaluation totals in ten-point bins.
     */
    protected function distribution(Collection $evaluations): array
    {
        $bins = [];
        for ($i = 0; $i < 10; $i++) {
            $lo = $i * 10;
            $hi = $i === 9 ? 100 : $lo + 9;
            $bins[$i] = ['range' => "{$lo}-{$hi}", 'count' => 0];
        }

        foreach ($evaluations as $e) {
            $index = (int) min(9, floor(((float) $e->total) / 10));
            $bins[$index]['count']++;
        }

        return array_values($bins);
    }

    protected function evaluatorActivity(Collection $evaluations, int $totalPapers): array
    {
        $byUser = $evaluations->groupBy('user_id');

        return User::evaluators()->get(['id', 'name'])->map(function (User $u) use ($byUser, $totalPapers) {
            $group = $byUser->get($u->id, collect());

            return [
                'id' => $u->id,
                'name' => $u->name,
                'submitted' => $group->count(),
                'expected' => $totalPapers,
                'percent' => $totalPapers > 0 ? (int) round($group->count() / $totalPapers * 100) : 0,
                'average' => $group->isNotEmpty() ? round($group->avg('total'), 2) : null,
                'last_at' => $group->max('submitted_at')?->toDateTimeString(),
            ];
        })->values()->all();
    }

    /**
     * The current first-ranked paper in each track.
     */
    protected function leaders(array $overallTracks): array
    {
        return collect($overallTracks)->map(function ($t) {
            $top = collect($t['papers'])->first(fn ($p) => $p['rank'] === 1);

            return [
                'track_id' => $t['track']['id'],
                'track' => "Track {$t['track']['number']}",
                'name' => $t['track']['name'],
                'is_locked' => $t['track']['is_locked'],
                'paper' => $top ? [
                    'id' => $top['id'],
                    'paper_no' => $top['paper_no'],
                    'title' => $top['title'],
                    'researcher' => $top['researcher'],
                    'average' => $top['average'],
                    'evaluations_count' => $top['evaluations_count'],
                ] : null,
                'evaluators_count' => $t['evaluators_count'],
            ];
        })->values()->all();
    }
    /**
     * Mean rating per criterion across all submitted evaluations, with the
     * criterion's maximum so the chart can show how close panels score to full marks.
     */
    protected function criterionAverages(): array
    {
        $averages = EvaluationScore::query()
            ->join('evaluations', 'evaluations.id', '=', 'evaluation_scores.evaluation_id')
            ->whereNotNull('evaluations.submitted_at')
            ->selectRaw('evaluation_scores.criterion_id, AVG(evaluation_scores.score) as avg_score, COUNT(*) as n')
            ->groupBy('evaluation_scores.criterion_id')
            ->get()
            ->keyBy('criterion_id');

        return Criterion::ordered()->get()->map(function (Criterion $c, int $i) use ($averages) {
            $row = $averages->get($c->id);
            $avg = $row ? round((float) $row->avg_score, 2) : null;

            return [
                'id' => $c->id,
                'code' => 'C' . ($i + 1),
                'name' => $c->name,
                'weight' => $c->weight,
                'average' => $avg,
                'percent' => $avg !== null && $c->weight > 0 ? round($avg / $c->weight * 100, 1) : null,
                'count' => $row ? (int) $row->n : 0,
            ];
        })->values()->all();
    }

    /**
     * How many tracks are complete, in progress, not started, or locked.
     */
    protected function trackStatus(array $tracks): array
    {
        $counts = ['Locked' => 0, 'Complete' => 0, 'In progress' => 0, 'Not started' => 0];

        foreach ($tracks as $t) {
            if ($t['is_locked']) {
                $counts['Locked']++;
            } elseif ($t['expected'] > 0 && $t['submitted'] >= $t['expected']) {
                $counts['Complete']++;
            } elseif ($t['submitted'] > 0) {
                $counts['In progress']++;
            } else {
                $counts['Not started']++;
            }
        }

        return collect($counts)->map(fn ($n, $label) => ['name' => $label, 'value' => $n])->values()->all();
    }
}
