<?php

use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Paper;
use App\Models\Track;
use App\Models\User;
use App\Services\ResultService;
use Database\Seeders\CriterionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrackSeeder;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TrackSeeder::class, CriterionSeeder::class]);

    $this->track = Track::where('number', 4)->firstOrFail();
    $this->paper = Paper::create([
        'track_id' => $this->track->id,
        'paper_no' => 'T4-001',
        'title' => 'Test Paper',
        'researcher' => 'Jane Doe',
    ]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(User::ROLE_ADMIN);

    $this->evaluator = User::factory()->create();
    $this->evaluator->assignRole(User::ROLE_EVALUATOR);

    $this->criteria = Criterion::ordered()->get();
});

function maxScores($criteria): array
{
    return $criteria->mapWithKeys(fn ($c) => [$c->id => $c->weight])->all();
}

test('criteria seed matches the official rubric weights', function () {
    expect($this->criteria)->toHaveCount(5);
    expect($this->criteria->sum('weight'))->toBe(100);
    expect($this->criteria->pluck('weight')->all())->toBe([25, 25, 15, 20, 15]);
});

test('evaluator can submit an evaluation and total is computed server-side', function () {
    $scores = maxScores($this->criteria);
    $scores[$this->criteria[0]->id] = 20.5; // 20.5 + 25 + 15 + 20 + 15 = 95.5

    $response = $this->actingAs($this->evaluator)->post(
        route('evaluator.papers.evaluate.store', $this->paper),
        ['scores' => $scores, 'comments' => 'Well presented.']
    );

    $response->assertRedirect(route('evaluator.workspace', ['track' => $this->track->id, 'paper' => $this->paper->id]));

    $evaluation = Evaluation::where('paper_id', $this->paper->id)
        ->where('user_id', $this->evaluator->id)
        ->first();

    expect($evaluation)->not->toBeNull();
    expect((float) $evaluation->total)->toBe(95.5);
    expect($evaluation->comments)->toBe('Well presented.');
    expect($evaluation->submitted_at)->not->toBeNull();
    expect($evaluation->scores)->toHaveCount(5);
});

test('re-submitting replaces the previous evaluation instead of duplicating it', function () {
    $scores = maxScores($this->criteria);

    $this->actingAs($this->evaluator)->post(route('evaluator.papers.evaluate.store', $this->paper), ['scores' => $scores]);

    $scores[$this->criteria[1]->id] = 10;
    $this->actingAs($this->evaluator)->post(route('evaluator.papers.evaluate.store', $this->paper), ['scores' => $scores]);

    expect(Evaluation::count())->toBe(1);
    expect((float) Evaluation::first()->total)->toBe(85.0);
    expect(Evaluation::first()->scores()->count())->toBe(5);
});

test('a rating above the criterion weight is rejected', function () {
    $scores = maxScores($this->criteria);
    $scores[$this->criteria[0]->id] = 26; // max is 25

    $response = $this->actingAs($this->evaluator)
        ->from(route('evaluator.workspace', ['paper' => $this->paper->id]))
        ->post(route('evaluator.papers.evaluate.store', $this->paper), ['scores' => $scores]);

    $response->assertSessionHasErrors("scores.{$this->criteria[0]->id}");
    expect(Evaluation::count())->toBe(0);
});

test('a missing criterion rating is rejected', function () {
    $scores = maxScores($this->criteria);
    unset($scores[$this->criteria[2]->id]);

    $response = $this->actingAs($this->evaluator)
        ->from(route('evaluator.workspace', ['paper' => $this->paper->id]))
        ->post(route('evaluator.papers.evaluate.store', $this->paper), ['scores' => $scores]);

    $response->assertSessionHasErrors();
    expect(Evaluation::count())->toBe(0);
});

test('negative ratings are rejected', function () {
    $scores = maxScores($this->criteria);
    $scores[$this->criteria[3]->id] = -1;

    $this->actingAs($this->evaluator)
        ->from(route('evaluator.workspace', ['paper' => $this->paper->id]))
        ->post(route('evaluator.papers.evaluate.store', $this->paper), ['scores' => $scores])
        ->assertSessionHasErrors("scores.{$this->criteria[3]->id}");
});

test('locked track rejects new and edited evaluations', function () {
    $this->track->update(['is_locked' => true]);

    $response = $this->actingAs($this->evaluator)
        ->from(route('evaluator.workspace', ['paper' => $this->paper->id]))
        ->post(route('evaluator.papers.evaluate.store', $this->paper), ['scores' => maxScores($this->criteria)]);

    $response->assertRedirect(route('evaluator.workspace', ['paper' => $this->paper->id]));
    $response->assertSessionHas('error');
    expect(Evaluation::count())->toBe(0);
});

test('admin can lock and unlock a track', function () {
    $this->actingAs($this->admin)
        ->patch(route('admin.tracks.lock', $this->track), ['is_locked' => true])
        ->assertRedirect();
    expect($this->track->fresh()->is_locked)->toBeTrue();

    $this->actingAs($this->admin)
        ->patch(route('admin.tracks.lock', $this->track), ['is_locked' => false])
        ->assertRedirect();
    expect($this->track->fresh()->is_locked)->toBeFalse();
});

test('admin cannot submit evaluations and evaluator cannot access admin pages', function () {
    $this->actingAs($this->admin)
        ->post(route('evaluator.papers.evaluate.store', $this->paper), ['scores' => maxScores($this->criteria)])
        ->assertForbidden();

    $this->actingAs($this->evaluator)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($this->evaluator)
        ->get(route('admin.results.track', $this->track))
        ->assertForbidden();
});

test('guests are redirected to login', function () {
    $this->get(route('evaluator.dashboard'))->assertRedirect(route('login'));
    $this->get(route('evaluator.workspace'))->assertRedirect(route('login'));
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
    $this->get('/')->assertOk();
});

test('login sends each role to its own dashboard', function () {
    $this->actingAs($this->admin)->get('/dashboard')->assertRedirect(route('admin.dashboard'));
    $this->actingAs($this->evaluator)->get('/dashboard')->assertRedirect(route('evaluator.dashboard'));
});

test('track results average across evaluators and rank with ties', function () {
    $second = User::factory()->create();
    $second->assignRole(User::ROLE_EVALUATOR);

    $paperB = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T4-002', 'title' => 'B', 'researcher' => 'R']);
    $paperC = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T4-003', 'title' => 'C', 'researcher' => 'R']);
    $paperD = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T4-004', 'title' => 'D (unevaluated)', 'researcher' => 'R']);

    $submit = function (User $user, Paper $paper, float $firstCriterion) {
        $scores = maxScores($this->criteria);
        $scores[$this->criteria[0]->id] = $firstCriterion;
        $this->actingAs($user)->post(route('evaluator.papers.evaluate.store', $paper), ['scores' => $scores]);
    };

    // Paper A: 100 and 90 -> avg 95
    $submit($this->evaluator, $this->paper, 25);
    $submit($second, $this->paper, 15);
    // Paper B: 95 and 95 -> avg 95 (tie with A)
    $submit($this->evaluator, $paperB, 20);
    $submit($second, $paperB, 20);
    // Paper C: only one evaluator, 80 -> avg 80
    $submit($this->evaluator, $paperC, 5);

    $result = app(ResultService::class)->forTrack($this->track);

    expect($result['evaluators'])->toHaveCount(2);

    $byNo = collect($result['papers'])->keyBy('paper_no');

    expect($byNo['T4-001']['average'])->toBe(95.0);
    expect($byNo['T4-002']['average'])->toBe(95.0);
    expect($byNo['T4-003']['average'])->toBe(80.0);
    expect($byNo['T4-004']['average'])->toBeNull();

    // Competition ranking: two tied at 1, next is 3, unevaluated has no rank and is last.
    expect($byNo['T4-001']['rank'])->toBe(1);
    expect($byNo['T4-002']['rank'])->toBe(1);
    expect($byNo['T4-003']['rank'])->toBe(3);
    expect($byNo['T4-004']['rank'])->toBeNull();
    expect(collect($result['papers'])->last()['paper_no'])->toBe('T4-004');

    // Per-evaluator totals appear in the sheet; missing evaluation is null.
    expect($byNo['T4-003']['totals'][$second->id])->toBeNull();
    expect($byNo['T4-003']['totals'][$this->evaluator->id])->toBe(80.0);
});

test('paper breakdown exposes each criterion rating and comments', function () {
    $scores = maxScores($this->criteria);
    $scores[$this->criteria[4]->id] = 7.25;

    $this->actingAs($this->evaluator)->post(
        route('evaluator.papers.evaluate.store', $this->paper),
        ['scores' => $scores, 'comments' => 'Improve slides.']
    );

    $result = app(ResultService::class)->forPaper($this->paper);

    expect($result['evaluations'])->toHaveCount(1);
    $row = $result['evaluations'][0];
    expect($row['submitted'])->toBeTrue();
    expect($row['scores'][$this->criteria[4]->id])->toBe(7.25);
    expect($row['total'])->toBe(92.25);
    expect($row['comments'])->toBe('Improve slides.');
    expect($result['average'])->toBe(92.25);
    expect($result['criterion_averages'][$this->criteria[4]->id])->toBe(7.25);
});

test('admin can manage papers and evaluators', function () {
    $this->actingAs($this->admin)->post(route('admin.papers.store'), [
        'track_id' => $this->track->id,
        'paper_no' => 'T4-099',
        'title' => 'New Paper',
        'researcher' => 'Someone',
        'affiliation' => '',
        'presentation_order' => '',
    ])->assertRedirect();
    expect(Paper::where('paper_no', 'T4-099')->exists())->toBeTrue();

    // Duplicate paper number is rejected.
    $this->actingAs($this->admin)->from(route('admin.papers.index'))->post(route('admin.papers.store'), [
        'track_id' => $this->track->id,
        'paper_no' => 'T4-099',
        'title' => 'Dup',
        'researcher' => 'Someone',
    ])->assertSessionHasErrors('paper_no');

    $this->actingAs($this->admin)->post(route('admin.evaluators.store'), [
        'name' => 'New Evaluator',
        'email' => 'NEW@Example.com',
        'password' => 'secret-pass-123',
    ])->assertRedirect();

    $created = User::where('email', 'new@example.com')->first();
    expect($created)->not->toBeNull();
    expect($created->isEvaluator())->toBeTrue();

    // Updating without a password keeps the old one.
    $oldHash = $created->password;
    $this->actingAs($this->admin)->put(route('admin.evaluators.update', $created), [
        'name' => 'Renamed',
        'email' => 'new@example.com',
        'password' => '',
    ])->assertRedirect();
    expect($created->fresh()->name)->toBe('Renamed');
    expect($created->fresh()->password)->toBe($oldHash);

    // Admin accounts cannot be edited or deleted through the evaluator endpoints.
    $this->actingAs($this->admin)->delete(route('admin.evaluators.destroy', $this->admin))->assertNotFound();

    $this->actingAs($this->admin)->delete(route('admin.evaluators.destroy', $created))->assertRedirect();
    expect(User::find($created->id))->toBeNull();
});

test('after submitting, the workspace advances to the next pending paper in the track', function () {
    $paperB = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T4-002', 'title' => 'B', 'researcher' => 'R']);
    $paperC = Paper::create(['track_id' => $this->track->id, 'paper_no' => 'T4-003', 'title' => 'C', 'researcher' => 'R']);

    $post = fn (Paper $p) => $this->actingAs($this->evaluator)
        ->post(route('evaluator.papers.evaluate.store', $p), ['scores' => maxScores($this->criteria)]);

    // A -> next pending is B
    $post($this->paper)->assertRedirect(route('evaluator.workspace', ['track' => $this->track->id, 'paper' => $paperB->id]));
    // C (skipping B) -> wraps around to B
    $post($paperC)->assertRedirect(route('evaluator.workspace', ['track' => $this->track->id, 'paper' => $paperB->id]));
    // B, last pending -> stays on B
    $post($paperB)->assertRedirect(route('evaluator.workspace', ['track' => $this->track->id, 'paper' => $paperB->id]));
});
