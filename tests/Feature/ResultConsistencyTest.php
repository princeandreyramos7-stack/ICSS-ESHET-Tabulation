<?php

use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Paper;
use App\Models\Track;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\ResultService;
use Database\Seeders\CriterionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrackSeeder;

/**
 * Every number shown anywhere in the system must come from the same rules:
 *   total    = sum of the five stored criterion ratings (2 decimals)
 *   average  = mean of submitted totals for the paper (2 decimals)
 *   rank     = competition ranking by average, ties share a rank,
 *              unevaluated papers last, ties ordered by natural paper number
 * and the dashboard, sheets and leaderboard must all agree.
 */
beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TrackSeeder::class, CriterionSeeder::class]);
    $this->criteria = Criterion::ordered()->get();
    $this->track = Track::where('number', 2)->firstOrFail();

    $this->evaluators = collect(range(1, 3))->map(function () {
        $u = User::factory()->create();
        $u->assignRole(User::ROLE_EVALUATOR);

        return $u;
    });
});

/** Submit the given five ratings (in criterion order) as $user for $paper. */
function rate(User $user, Paper $paper, array $ratings): void
{
    $criteria = Criterion::ordered()->get();
    $scores = [];
    foreach ($criteria as $i => $c) {
        $scores[$c->id] = $ratings[$i];
    }
    test()->actingAs($user)->post(route('evaluator.papers.evaluate.store', $paper), ['scores' => $scores])->assertRedirect();
}

test('the stored total is exactly the sum of the stored ratings, even with awkward decimals', function () {
    $paper = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T2-001', 'title' => 'A', 'researcher' => 'R']);

    // Classic floating-point traps: 0.1 + 0.2 style values.
    rate($this->evaluators[0], $paper, [10.1, 20.2, 5.3, 15.4, 5.5]);

    $evaluation = Evaluation::with('scores')->firstOrFail();
    $storedSum = round($evaluation->scores->sum(fn ($s) => (float) $s->score), 2);

    expect((float) $evaluation->total)->toBe(56.5);
    expect((float) $evaluation->total)->toBe($storedSum);
    expect($evaluation->scores->pluck('score')->map(fn ($s) => (float) $s)->all())->toBe([10.1, 20.2, 5.3, 15.4, 5.5]);
});

test('average is the mean of submitted totals and the same on every screen', function () {
    $paper = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T2-001', 'title' => 'A', 'researcher' => 'R']);

    rate($this->evaluators[0], $paper, [25, 25, 15, 20, 15]);     // 100
    rate($this->evaluators[1], $paper, [20.5, 20, 10, 15, 10]);   // 75.5
    rate($this->evaluators[2], $paper, [10, 10, 5, 5, 5]);        // 35
    // (100 + 75.5 + 35) / 3 = 70.1666... -> 70.17

    $results = app(ResultService::class);
    $track = $results->forTrack($this->track);
    $detail = $results->forPaper($paper);
    $overall = $results->overall();
    $analytics = app(AnalyticsService::class)->dashboard();

    $fromTrack = $track['papers'][0]['average'];
    $fromPaper = $detail['average'];
    $fromOverall = $overall['tracks'][1]['papers'][0]['average'];
    $fromLeaderboard = $overall['leaderboard'][0]['average'];
    $fromDashboardTop = $analytics['top_papers'][0]['average'];
    $fromDashboardLeader = $analytics['leaders'][1]['paper']['average'];
    $fromDashboardTrackAvg = $analytics['average_by_track'][1]['average'];

    expect($fromTrack)->toBe(70.17);
    expect([$fromPaper, $fromOverall, $fromLeaderboard, $fromDashboardTop, $fromDashboardLeader, $fromDashboardTrackAvg])
        ->each->toBe(70.17);

    // Per-evaluator totals on the sheet match what was stored.
    $totals = $track['papers'][0]['totals'];
    expect($totals[$this->evaluators[0]->id])->toBe(100.0);
    expect($totals[$this->evaluators[1]->id])->toBe(75.5);
    expect($totals[$this->evaluators[2]->id])->toBe(35.0);
    $second = collect($detail['evaluations'])->firstWhere('evaluator_id', $this->evaluators[1]->id);
    expect($second['total'])->toBe(75.5);

    // Sum of the per-criterion averages reproduces the average (within rounding).
    $criterionSum = array_sum($detail['criterion_averages']);
    expect(abs($criterionSum - $fromPaper))->toBeLessThan(0.05);

    // Overall average across all submissions is the mean of totals.
    expect($analytics['overall_average'])->toBe(70.17);
});

test('papers with fewer submissions are averaged over what was submitted and flagged by count', function () {
    $paper = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T2-001', 'title' => 'A', 'researcher' => 'R']);

    rate($this->evaluators[0], $paper, [25, 25, 15, 20, 15]); // 100
    rate($this->evaluators[1], $paper, [20, 20, 10, 15, 10]); // 75

    $row = app(ResultService::class)->forTrack($this->track)['papers'][0];

    expect($row['average'])->toBe(87.5);
    expect($row['evaluations_count'])->toBe(2);
    expect($row['totals'][$this->evaluators[2]->id])->toBeNull();
});

test('ranking uses competition ranks, shares ties, orders ties naturally, and puts unevaluated papers last', function () {
    $p = collect(['T2-9', 'T2-10', 'T2-2', 'T2-3', 'T2-4'])->mapWithKeys(fn ($no) => [
        $no => Paper::create(['track_id' => $this->track->id, 'paper_no' => $no, 'title' => $no, 'researcher' => 'R']),
    ]);

    // T2-9 and T2-10 tie at 90; T2-2 = 80; T2-3 = 95; T2-4 unevaluated.
    rate($this->evaluators[0], $p['T2-9'], [25, 25, 10, 20, 10]);
    rate($this->evaluators[0], $p['T2-10'], [25, 25, 10, 20, 10]);
    rate($this->evaluators[0], $p['T2-2'], [20, 20, 10, 20, 10]);
    rate($this->evaluators[0], $p['T2-3'], [25, 25, 15, 20, 10]);

    $rows = app(ResultService::class)->forTrack($this->track)['papers'];
    $order = array_map(fn ($r) => [$r['paper_no'], $r['rank'], $r['average']], $rows);

    expect($order)->toBe([
        ['T2-3', 1, 95.0],
        ['T2-9', 2, 90.0],   // natural order: 9 before 10
        ['T2-10', 2, 90.0],  // shared rank
        ['T2-2', 4, 80.0],   // competition ranking skips 3
        ['T2-4', null, null],
    ]);

    // The cross-track leaderboard applies the identical rule.
    $board = app(ResultService::class)->overall()['leaderboard'];
    expect(array_map(fn ($r) => [$r['paper_no'], $r['overall_rank']], $board))->toBe([
        ['T2-3', 1],
        ['T2-9', 2],
        ['T2-10', 2],
        ['T2-2', 4],
    ]);
});

test('ties are decided on the rounded average that is displayed, never on hidden decimals', function () {
    $a = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T2-001', 'title' => 'A', 'researcher' => 'R']);
    $b = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T2-002', 'title' => 'B', 'researcher' => 'R']);

    // A: (90 + 90 + 90.01) / 3 = 90.00333 -> 90.00 ; B: 90 flat. Displayed equal, so tied.
    rate($this->evaluators[0], $a, [25, 25, 10, 20, 10]);
    rate($this->evaluators[1], $a, [25, 25, 10, 20, 10]);
    rate($this->evaluators[2], $a, [25, 25, 10, 20, 10.01]);
    rate($this->evaluators[0], $b, [25, 25, 10, 20, 10]);
    rate($this->evaluators[1], $b, [25, 25, 10, 20, 10]);
    rate($this->evaluators[2], $b, [25, 25, 10, 20, 10]);

    $rows = app(ResultService::class)->forTrack($this->track)['papers'];

    expect($rows[0]['average'])->toBe(90.0);
    expect($rows[1]['average'])->toBe(90.0);
    expect($rows[0]['rank'])->toBe(1);
    expect($rows[1]['rank'])->toBe(1);
});

test('progress counts are papers times evaluators and submissions never exceed expected', function () {
    Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T2-001', 'title' => 'A', 'researcher' => 'R']);
    $paper = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T2-002', 'title' => 'B', 'researcher' => 'R']);

    rate($this->evaluators[0], $paper, [25, 25, 15, 20, 15]);
    rate($this->evaluators[0], $paper, [20, 20, 10, 15, 10]); // resubmit: must not double count

    $progress = app(ResultService::class)->progress();
    $trackRow = collect($progress['tracks'])->firstWhere('id', $this->track->id);

    expect($trackRow['expected'])->toBe(2 * 3);
    expect($trackRow['submitted'])->toBe(1);
    expect($trackRow['percent'])->toBe(17);
    expect($progress['expected'])->toBe(6);
    expect($progress['submitted'])->toBe(1);
});

test('the dashboard track average equals the mean of the sheet averages, not of raw submissions', function () {
    $a = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T2-001', 'title' => 'A', 'researcher' => 'R']);
    $b = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T2-002', 'title' => 'B', 'researcher' => 'R']);

    // A is rated by three evaluators (avg 100), B by one (avg 50).
    rate($this->evaluators[0], $a, [25, 25, 15, 20, 15]);
    rate($this->evaluators[1], $a, [25, 25, 15, 20, 15]);
    rate($this->evaluators[2], $a, [25, 25, 15, 20, 15]);
    rate($this->evaluators[0], $b, [12.5, 12.5, 7.5, 10, 7.5]);

    $analytics = app(AnalyticsService::class)->dashboard();
    $trackRow = collect($analytics['average_by_track'])->firstWhere('track', 'T2');

    // Mean of paper averages: (100 + 50) / 2 = 75. A raw-submission mean would be 87.5.
    expect($trackRow['average'])->toBe(75.0);
    expect($trackRow['count'])->toBe(2);
});
