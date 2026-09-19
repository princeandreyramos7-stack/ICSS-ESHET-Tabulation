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

/** Count page objects in DomPDF output; page dictionaries are written uncompressed. */
function pdfPageCount(string $content): int
{
    return preg_match_all('~/Type\s*/Page(?![s/])~', $content);
}

/** [width, height] of the first MediaBox in points. */
function pdfPageSize(string $content): array
{
    preg_match('~/MediaBox\s*\[\s*[\d.]+\s+[\d.]+\s+([\d.]+)\s+([\d.]+)\s*\]~', $content, $m);

    return [(float) ($m[1] ?? 0), (float) ($m[2] ?? 0)];
}

function assertPdfDownload($response, string $filenamePrefix, ?int $pages = null): string
{
    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))
        ->toContain('attachment')
        ->toContain($filenamePrefix);

    $content = $response->getContent();
    expect($content)->toStartWith('%PDF-');

    // Every sheet is A4 landscape.
    [$w, $h] = pdfPageSize($content);
    expect($w)->toBeGreaterThan($h);

    if ($pages !== null) {
        expect(pdfPageCount($content))->toBe($pages);
    }

    return $content;
}

/** Fill every track with a panel and several scored papers, like a real conference day. */
function seedFullConference(int $papersPerTrack = 8, int $evaluatorsPerTrack = 2): void
{
    $criteria = Criterion::ordered()->get();
    foreach (Track::orderBy('number')->get() as $track) {
        $panel = collect(range(1, $evaluatorsPerTrack))->map(function ($i) use ($track) {
            $u = User::factory()->create(['track_id' => $track->id, 'name' => "Evaluator {$track->number}-{$i}"]);
            $u->assignRole(User::ROLE_EVALUATOR);

            return $u;
        });
        for ($n = 1; $n <= $papersPerTrack; $n++) {
            $paper = Paper::create([
                'track_id' => $track->id,
                'paper_no' => "P{$track->number}" . str_pad((string) $n, 2, '0', STR_PAD_LEFT),
                'title' => "Paper {$n}: A Web-Based Management and Monitoring System for Sustainable Institutions",
                'researcher' => "Researcher {$n}",
            ]);
            foreach ($panel as $j => $evaluator) {
                $scores = $criteria->mapWithKeys(fn ($c) => [$c->id => max(1, $c->weight - (($n + $j) % 6))])->all();
                test()->actingAs($evaluator)->post(route('evaluator.papers.evaluate.store', $paper), [
                    'scores' => $scores,
                    'comments' => $j === 0 ? 'Clear and well presented.' : '',
                ]);
            }
        }
    }
}

test('overall results download as a two-page landscape PDF', function () {
    assertPdfDownload(
        $this->actingAs($this->admin)->get(route('admin.results.overall.pdf')),
        'Overall-Results-',
        pages: 2
    );
});

test('overall results PDF still renders with no evaluations', function () {
    \App\Models\Evaluation::query()->delete();

    assertPdfDownload(
        $this->actingAs($this->admin)->get(route('admin.results.overall.pdf')),
        'Overall-Results-',
        pages: 2
    );
});

test('track results download as a one-page landscape PDF', function () {
    assertPdfDownload(
        $this->actingAs($this->admin)->get(route('admin.results.track.pdf', $this->track)),
        'Track-',
        pages: 1
    );
});

test('paper results download as a one-page landscape PDF', function () {
    assertPdfDownload(
        $this->actingAs($this->admin)->get(route('admin.results.paper.pdf', $this->paper)),
        'Paper-',
        pages: 1
    );
});

test('a full conference still fits the page targets by shrinking', function () {
    seedFullConference(papersPerTrack: 10, evaluatorsPerTrack: 3);

    assertPdfDownload(
        $this->actingAs($this->admin)->get(route('admin.results.overall.pdf')),
        'Overall-Results-',
        pages: 2
    );

    $track = Track::where('number', 2)->firstOrFail();
    assertPdfDownload(
        $this->actingAs($this->admin)->get(route('admin.results.track.pdf', $track)),
        'Track-',
        pages: 1
    );
});

test('evaluators cannot download result PDFs', function () {
    $evaluator = User::factory()->create(['track_id' => $this->track->id]);
    $evaluator->assignRole(User::ROLE_EVALUATOR);

    $this->actingAs($evaluator)->get(route('admin.results.overall.pdf'))->assertForbidden();
});
