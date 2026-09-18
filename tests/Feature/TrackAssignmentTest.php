<?php

use App\Models\Criterion;
use App\Models\Paper;
use App\Models\Track;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\ResultService;
use Database\Seeders\CriterionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrackSeeder;

/*
 * Parallel-session workflow: seven tracks, and every evaluator sits on the
 * panel of exactly one track. Sheets, progress and permissions must all
 * follow that assignment.
 */
beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TrackSeeder::class, CriterionSeeder::class]);
    $this->criteria = Criterion::ordered()->get();

    $this->track4 = Track::where('number', 4)->firstOrFail();
    $this->track5 = Track::where('number', 5)->firstOrFail();

    $this->paper4 = Paper::create(['track_id' => $this->track4->id, 'paper_no' => 'T4-001', 'title' => 'A', 'researcher' => 'R']);
    $this->paper5 = Paper::create(['track_id' => $this->track5->id, 'paper_no' => 'T5-001', 'title' => 'B', 'researcher' => 'R']);

    $panel = fn (Track $track) => tap(User::factory()->create(['track_id' => $track->id]))->assignRole(User::ROLE_EVALUATOR);
    $this->panel4 = collect([$panel($this->track4), $panel($this->track4), $panel($this->track4)]);
    $this->panel5 = collect([$panel($this->track5)]);

    $this->admin = tap(User::factory()->create())->assignRole(User::ROLE_ADMIN);
    $this->full = $this->criteria->mapWithKeys(fn ($c) => [$c->id => $c->weight])->all();
});

test('the seeder provides the seven parallel-session tracks with venue and chairs', function () {
    expect(Track::count())->toBe(7);
    expect($this->track5->name)->toBe('Cross-Disciplinary in Legal Justice, Human Arts and Architecture');
    expect(Track::where('number', 7)->value('venue'))->toBe('SOM Amphitheater, Allied Health Building');
    expect($this->track4->session_chair)->toBe('Dr. Crestian A. Agustin');
    expect($this->track4->co_session_chair)->toBe('Dr. Michelle G. Quijano');
});

test('an evaluator can only score papers in their own track', function () {
    $member = $this->panel4->first();

    $this->actingAs($member)
        ->post(route('evaluator.papers.evaluate.store', $this->paper5), ['scores' => $this->full])
        ->assertForbidden();

    $this->actingAs($member)
        ->post(route('evaluator.papers.evaluate.store', $this->paper4), ['scores' => $this->full])
        ->assertRedirect();

    $this->assertDatabaseCount('evaluations', 1);

    // Unassigned evaluators cannot score anything.
    $unassigned = tap(User::factory()->create(['track_id' => null]))->assignRole(User::ROLE_EVALUATOR);
    $this->actingAs($unassigned)
        ->post(route('evaluator.papers.evaluate.store', $this->paper4), ['scores' => $this->full])
        ->assertForbidden();
});

test('result sheets list only the panel of that track', function () {
    $results = app(ResultService::class);

    $sheet4 = $results->forTrack($this->track4);
    $sheet5 = $results->forTrack($this->track5);

    expect(collect($sheet4['evaluators'])->pluck('id')->sort()->values()->all())
        ->toBe($this->panel4->pluck('id')->sort()->values()->all());
    expect(collect($sheet5['evaluators'])->pluck('id')->all())->toBe($this->panel5->pluck('id')->all());

    expect($sheet4['track']['venue'])->toBe('Room 203, New CEAT Building');
    expect($sheet4['track']['session_chair'])->toBe('Dr. Crestian A. Agustin');

    // The per-paper breakdown follows the paper's track panel too.
    $breakdown = $results->forPaper($this->paper5);
    expect($breakdown['evaluations'])->toHaveCount(1);
    expect($breakdown['evaluations'][0]['evaluator_id'])->toBe($this->panel5->first()->id);
});

test('expected evaluations are papers times the size of each track panel', function () {
    Paper::create(['track_id' => $this->track4->id, 'paper_no' => 'T4-002', 'title' => 'C', 'researcher' => 'R']);

    $progress = app(ResultService::class)->progress();
    $rows = collect($progress['tracks'])->keyBy('id');

    expect($rows[$this->track4->id]['evaluators_count'])->toBe(3);
    expect($rows[$this->track4->id]['expected'])->toBe(2 * 3);
    expect($rows[$this->track5->id]['expected'])->toBe(1 * 1);
    expect($rows[Track::where('number', 1)->value('id')]['expected'])->toBe(0);
    expect($progress['expected'])->toBe(7);
    expect($progress['evaluators'])->toBe(4);

    // Evaluator activity counts against their own track's papers only.
    $activity = collect(app(AnalyticsService::class)->dashboard()['evaluator_activity'])->keyBy('id');
    expect($activity[$this->panel4->first()->id]['expected'])->toBe(2);
    expect($activity[$this->panel4->first()->id]['track'])->toBe('T4');
    expect($activity[$this->panel5->first()->id]['expected'])->toBe(1);
});

test('the admin evaluators page groups the panel by track and offers every track', function () {
    $this->actingAs($this->admin)->get(route('admin.evaluators.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Evaluators/Index')
            ->has('evaluators', 4)
            ->where('evaluators.0.track_number', 4)
            ->where('evaluators.3.track_number', 5)
            ->has('tracks', 7));
});
