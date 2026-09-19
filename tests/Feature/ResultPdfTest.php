<?php

use App\Models\Criterion;
use App\Models\Paper;
use App\Models\Track;
use App\Models\User;
use Database\Seeders\CriterionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrackSeeder;

beforeEach(function () {
    $this->seed([RolePermissionSeeder::class, TrackSeeder::class, CriterionSeeder::class]);

    $this->track = Track::where('number', 1)->firstOrFail();
    $this->paper = Paper::create([
        'track_id' => $this->track->id,
        'paper_no' => 'T1-001',
        'title' => 'Peña – Résumé of "Findings"',
        'researcher' => 'R. Searcher',
    ]);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(User::ROLE_ADMIN);

    $evaluator = User::factory()->create(['track_id' => $this->track->id]);
    $evaluator->assignRole(User::ROLE_EVALUATOR);

    $scores = Criterion::ordered()->get()->mapWithKeys(fn ($c) => [$c->id => $c->weight - 1])->all();
    $this->actingAs($evaluator)->post(route('evaluator.papers.evaluate.store', $this->paper), [
        'scores' => $scores,
        'comments' => 'Comment with an ñ and a — dash.',
    ]);
});

function assertPdfDownload($response, string $filenamePrefix): void
{
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment')
        ->toContain($filenamePrefix);
    expect($response->getContent())->toStartWith('%PDF-');
}

test('overall results download as a PDF', function () {
    assertPdfDownload(
        $this->actingAs($this->admin)->get(route('admin.results.overall.pdf')),
        'Overall-Results-'
    );
});

test('overall results PDF still renders with no evaluations', function () {
    \App\Models\Evaluation::query()->delete();

    assertPdfDownload(
        $this->actingAs($this->admin)->get(route('admin.results.overall.pdf')),
        'Overall-Results-'
    );
});

test('track results download as a PDF', function () {
    assertPdfDownload(
        $this->actingAs($this->admin)->get(route('admin.results.track.pdf', $this->track)),
        'Track-'
    );
});

test('paper results download as a PDF', function () {
    assertPdfDownload(
        $this->actingAs($this->admin)->get(route('admin.results.paper.pdf', $this->paper)),
        'Paper-'
    );
});

test('evaluators cannot download result PDFs', function () {
    $evaluator = User::factory()->create(['track_id' => $this->track->id]);
    $evaluator->assignRole(User::ROLE_EVALUATOR);

    $this->actingAs($evaluator)->get(route('admin.results.overall.pdf'))->assertForbidden();
});
