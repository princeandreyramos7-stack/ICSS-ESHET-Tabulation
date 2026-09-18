<?php

use App\Models\Criterion;
use App\Models\Paper;
use App\Models\Track;
use App\Models\User;
use Database\Seeders\CriterionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrackSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TrackSeeder::class, CriterionSeeder::class]);

    $this->track = Track::where('number', 1)->firstOrFail();
    $this->paper = Paper::create([
        'track_id' => $this->track->id,
        'paper_no' => 'T1-001',
        'title' => 'Render Test',
        'researcher' => 'R. Searcher',
    ]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(User::ROLE_ADMIN);

    $this->evaluator = User::factory()->create();
    $this->evaluator->assignRole(User::ROLE_EVALUATOR);

    $this->submitSample = function () {
        $scores = Criterion::ordered()->get()->mapWithKeys(fn ($c) => [$c->id => $c->weight - 1])->all();
        $this->actingAs($this->evaluator)->post(route('evaluator.papers.evaluate.store', $this->paper), [
            'scores' => $scores,
            'comments' => 'ok',
        ]);
    };
});

test('every admin page renders with its props', function () {
    ($this->submitSample)();

    $this->actingAs($this->admin)->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Dashboard')
            ->has('analytics.progress.tracks', 6)
            ->where('analytics.progress.papers', 1)
            ->where('analytics.progress.evaluators', 1)
            ->where('analytics.progress.submitted', 1)
            ->has('analytics.timeline', 1)
            ->where('analytics.timeline.0.cumulative', 1)
            ->has('analytics.average_by_track', 6)
            ->where('analytics.average_by_track.0.average', 95)
            ->has('analytics.distribution', 10)
            ->where('analytics.distribution.9.count', 1)
            ->has('analytics.evaluator_activity', 1)
            ->where('analytics.evaluator_activity.0.submitted', 1)
            ->has('analytics.leaders', 6)
            ->where('analytics.leaders.0.paper.average', 95)
            ->has('analytics.recent', 1)
            ->where('analytics.overall_average', 95)
            ->has('analytics.criterion_averages', 5)
            ->where('analytics.criterion_averages.0.code', 'C1')
            ->where('analytics.criterion_averages.0.average', 24)
            ->has('analytics.top_papers', 1)
            ->where('analytics.top_papers.0.average', 95)
            ->has('analytics.track_status', 4)
            ->has('tracks', 6)
            ->has('conference.name')
            ->where('auth.user.role', 'admin'));

    $this->actingAs($this->admin)->get(route('admin.papers.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Papers/Index')
            ->has('papers', 1)
            ->where('papers.0.evaluations_count', 1)
            ->has('tracks', 6));

    $this->actingAs($this->admin)->get(route('admin.evaluators.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Evaluators/Index')
            ->has('evaluators', 1)
            ->where('evaluators.0.evaluations_count', 1));

    $this->actingAs($this->admin)->get(route('admin.tracks.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Admin/Tracks/Index')->has('progress.tracks', 6));

    $this->actingAs($this->admin)->get(route('admin.results.track', $this->track))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Track')
            ->has('result.evaluators', 1)
            ->has('result.papers', 1)
            ->where('result.papers.0.average', 95)
            ->where('result.papers.0.rank', 1));

    $this->actingAs($this->admin)->get(route('admin.results.paper', $this->paper))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Paper')
            ->has('result.criteria', 5)
            ->has('result.evaluations', 1)
            ->where('result.evaluations.0.comments', 'ok')
            ->where('result.average', 95));

    $this->actingAs($this->admin)->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Profile/Edit'));
});

test('the evaluator workspace renders with every track, paper and prior rating', function () {
    ($this->submitSample)();

    $pending = Paper::create([
        'track_id' => $this->track->id,
        'paper_no' => 'T1-002',
        'title' => 'Pending',
        'researcher' => 'P. Ending',
    ]);

    // Default position: first pending paper of the first track with pending work.
    $this->actingAs($this->evaluator)->get(route('evaluator.workspace'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Evaluator/Workspace')
            ->has('tracks', 6)
            ->has('criteria', 5)
            ->has('tracks.0.papers', 2)
            ->where('tracks.0.evaluated_count', 1)
            ->where('tracks.0.papers.0.submitted', true)
            ->where('tracks.0.papers.0.total', 95)
            ->where('tracks.0.papers.0.evaluation.comments', 'ok')
            ->has('tracks.0.papers.0.evaluation.scores', 5)
            ->where('tracks.0.papers.1.submitted', false)
            ->where('tracks.0.papers.1.evaluation', null)
            ->where('selected.track_id', $this->track->id)
            ->where('selected.paper_id', $pending->id)
            ->where('totals.papers', 2)
            ->where('totals.evaluated', 1)
            ->where('auth.user.role', 'evaluator'));

    // Explicit ?paper= wins and resolves its track.
    $this->actingAs($this->evaluator)->get(route('evaluator.workspace', ['paper' => $this->paper->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selected.track_id', $this->track->id)
            ->where('selected.paper_id', $this->paper->id));

    // ?track= without a paper opens that track's first pending paper.
    $otherTrack = Track::where('number', 3)->firstOrFail();
    $this->actingAs($this->evaluator)->get(route('evaluator.workspace', ['track' => $otherTrack->id]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('selected.track_id', $otherTrack->id)
            ->where('selected.paper_id', null));
});

test('login page renders with conference branding', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Auth/Login')->has('conference.name'));
});

test('the evaluator dashboard renders progress, next paper, recent submissions and rubric', function () {
    ($this->submitSample)();

    $pending = Paper::create([
        'track_id' => $this->track->id,
        'paper_no' => 'T1-002',
        'title' => 'Pending',
        'researcher' => 'P. Ending',
    ]);

    $this->actingAs($this->evaluator)->get(route('evaluator.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Evaluator/Dashboard')
            ->has('tracks', 6)
            ->where('tracks.0.papers_count', 2)
            ->where('tracks.0.evaluated_count', 1)
            ->where('tracks.0.next_paper_id', $pending->id)
            ->where('totals.papers', 2)
            ->where('totals.evaluated', 1)
            ->where('totals.pending', 1)
            ->where('totals.average_given', 95)
            ->where('next_paper.id', $pending->id)
            ->where('next_paper.track_number', 1)
            ->has('recent', 1)
            ->where('recent.0.paper_no', 'T1-001')
            ->where('recent.0.total', 95)
            ->has('criteria', 5));

    // Once everything is evaluated there is no "next paper".
    $scores = Criterion::ordered()->get()->mapWithKeys(fn ($c) => [$c->id => $c->weight])->all();
    $this->actingAs($this->evaluator)->post(route('evaluator.papers.evaluate.store', $pending), ['scores' => $scores]);

    $this->actingAs($this->evaluator)->get(route('evaluator.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('next_paper', null)
            ->where('totals.pending', 0)
            ->has('recent', 2));
});

test('the overall results page renders every track and a cross-track leaderboard', function () {
    ($this->submitSample)();

    $this->actingAs($this->admin)->get(route('admin.results.overall'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Admin/Results/Overall')
            ->has('result.tracks', 6)
            ->has('result.tracks.0.papers', 1)
            ->where('result.tracks.0.papers.0.rank', 1)
            ->has('result.leaderboard', 1)
            ->where('result.leaderboard.0.overall_rank', 1)
            ->where('result.leaderboard.0.average', 95)
            ->where('result.leaderboard.0.track_number', 1)
            ->has('result.evaluators', 1)
            ->where('result.all_locked', false));

    $this->actingAs($this->evaluator)->get(route('admin.results.overall'))->assertForbidden();
});
