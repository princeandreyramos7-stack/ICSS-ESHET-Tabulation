<?php

use App\Models\Paper;
use App\Models\Track;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TrackSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');

    $this->seed([RolePermissionSeeder::class, TrackSeeder::class]);

    $this->track = Track::where('number', 1)->firstOrFail();
    $this->otherTrack = Track::where('number', 2)->firstOrFail();

    $this->admin = User::factory()->create();
    $this->admin->assignRole(User::ROLE_ADMIN);

    $this->evaluator = User::factory()->create(['track_id' => $this->track->id]);
    $this->evaluator->assignRole(User::ROLE_EVALUATOR);
});

function uploadPaperWithManuscript($test, string $originalName = 'manuscript.pdf', string $paperNo = 'T1-001'): Paper
{
    $file = UploadedFile::fake()->createWithContent($originalName, '%PDF-1.4 fake manuscript body');

    $test->actingAs($test->admin)->post(route('admin.papers.store'), [
        'track_id' => $test->track->id,
        'paper_no' => $paperNo,
        'title' => 'Test Paper',
        'researcher' => 'Jane Doe',
        'manuscript' => $file,
    ])->assertRedirect()->assertSessionHasNoErrors();

    return Paper::where('paper_no', $paperNo)->firstOrFail();
}

test('admin upload stores the manuscript on the default disk with a safe filename', function () {
    $paper = uploadPaperWithManuscript($this, 'manuscript.pdf', 'T1/001 draft');

    expect($paper->manuscript_path)->toStartWith("manuscripts/track-{$this->track->id}/paper-t1001-draft-");
    expect($paper->hasManuscript())->toBeTrue();
    expect($paper->manuscript_url)->toBe(route('manuscripts.show', $paper));
    expect($paper->manuscript_download_url)->toBe(route('manuscripts.download', $paper));
    Storage::disk('local')->assertExists($paper->manuscript_path);
});

test('evaluator on the paper track can view the manuscript inline', function () {
    $paper = uploadPaperWithManuscript($this);

    $response = $this->actingAs($this->evaluator)->get(route('manuscripts.show', $paper));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertHeader('Content-Length', (string) strlen('%PDF-1.4 fake manuscript body'));
    expect($response->headers->get('Content-Disposition'))->toStartWith('inline; filename=manuscript.pdf');
    expect($response->streamedContent())->toBe('%PDF-1.4 fake manuscript body');
});

test('download route forces an attachment', function () {
    $paper = uploadPaperWithManuscript($this);

    $response = $this->actingAs($this->evaluator)->get(route('manuscripts.download', $paper));

    $response->assertOk();
    expect($response->headers->get('Content-Disposition'))->toStartWith('attachment; filename=manuscript.pdf');
    expect($response->streamedContent())->toBe('%PDF-1.4 fake manuscript body');
});

test('admin can view any manuscript', function () {
    $paper = uploadPaperWithManuscript($this);

    $this->actingAs($this->admin)->get(route('manuscripts.show', $paper))->assertOk();
});

test('non-ascii original filename gets an ascii fallback and never breaks the header', function () {
    $paper = uploadPaperWithManuscript($this, 'Peña – Résumé "final" 100%.pdf');

    $response = $this->actingAs($this->admin)->get(route('manuscripts.show', $paper));

    $response->assertOk();
    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toStartWith('inline;');
    expect($disposition)->toContain("filename*=utf-8''");
    // The plain fallback must be ASCII-only.
    preg_match('/filename="?([^";]+)"?/', $disposition, $m);
    expect($m[1])->toMatch('/^[\x20-\x7e]+$/');
});

test('missing original name falls back to the paper number', function () {
    $paper = uploadPaperWithManuscript($this);
    $paper->forceFill(['manuscript_original_name' => null])->save();

    $response = $this->actingAs($this->admin)->get(route('manuscripts.download', $paper));

    expect($response->headers->get('Content-Disposition'))->toBe('attachment; filename=Paper-T1-001.pdf');
});

test('evaluator from another track is forbidden', function () {
    $paper = uploadPaperWithManuscript($this);
    $outsider = User::factory()->create(['track_id' => $this->otherTrack->id]);
    $outsider->assignRole(User::ROLE_EVALUATOR);

    $this->actingAs($outsider)->get(route('manuscripts.show', $paper))->assertForbidden();
});

test('evaluator without a track is forbidden', function () {
    $paper = uploadPaperWithManuscript($this);
    $unassigned = User::factory()->create(['track_id' => null]);
    $unassigned->assignRole(User::ROLE_EVALUATOR);

    $this->actingAs($unassigned)->get(route('manuscripts.show', $paper))->assertForbidden();
});

test('paper without an upload returns 404', function () {
    $paper = Paper::create([
        'track_id' => $this->track->id,
        'paper_no' => 'T1-002',
        'title' => 'No file',
        'researcher' => 'Jane Doe',
    ]);

    $this->actingAs($this->admin)->get(route('manuscripts.show', $paper))->assertNotFound();
});

test('missing file on disk returns 404 rather than 500', function () {
    $paper = Paper::create([
        'track_id' => $this->track->id,
        'paper_no' => 'T1-003',
        'title' => 'Dangling path',
        'researcher' => 'Jane Doe',
        'manuscript_path' => 'manuscripts/track-1/does-not-exist.pdf',
        'manuscript_original_name' => 'x.pdf',
    ]);

    expect($paper->hasManuscript())->toBeFalse();
    expect($paper->manuscript_url)->toBeNull();
    $this->actingAs($this->admin)->get(route('manuscripts.show', $paper))->assertNotFound();
});

test('deleting the file after upload makes the workspace hide the link instead of failing', function () {
    $paper = uploadPaperWithManuscript($this);
    Storage::disk('local')->delete($paper->manuscript_path);

    $this->actingAs($this->evaluator)
        ->get(route('evaluator.workspace', ['track' => $this->track->id, 'paper' => $paper->id]))
        ->assertOk();
});

test('guest is redirected to login', function () {
    $paper = uploadPaperWithManuscript($this);
    auth()->logout();

    $this->get(route('manuscripts.show', $paper))->assertRedirect(route('login'));
    $this->get(route('manuscripts.download', $paper))->assertRedirect(route('login'));
});

test('editing a paper with a new PDF works through the spoofed PUT the form sends', function () {
    $paper = uploadPaperWithManuscript($this);
    $oldPath = $paper->manuscript_path;

    // Inertia sends multipart edits as POST + _method=put; this is what broke with a plain POST (405).
    $this->actingAs($this->admin)->post(route('admin.papers.update', $paper), [
        '_method' => 'put',
        'track_id' => $this->track->id,
        'paper_no' => 'T1-001',
        'title' => 'Edited title',
        'researcher' => 'Jane Doe',
        'manuscript' => UploadedFile::fake()->createWithContent('revised.pdf', '%PDF-1.4 revised'),
    ])->assertRedirect()->assertSessionHasNoErrors();

    $paper->refresh();
    expect($paper->title)->toBe('Edited title');
    expect($paper->manuscript_original_name)->toBe('revised.pdf');
    expect($paper->manuscript_path)->not->toBe($oldPath);
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($paper->manuscript_path);
});

test('editing without choosing a file keeps the existing manuscript', function () {
    $paper = uploadPaperWithManuscript($this);

    $this->actingAs($this->admin)->post(route('admin.papers.update', $paper), [
        '_method' => 'put',
        'track_id' => $this->track->id,
        'paper_no' => 'T1-001',
        'title' => 'Edited title',
        'researcher' => 'Jane Doe',
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect($paper->fresh()->hasManuscript())->toBeTrue();
});

test('the papers page tells the form the effective upload limit', function () {
    $this->actingAs($this->admin)->get(route('admin.papers.index'))
        ->assertInertia(fn ($page) => $page
            ->component('Admin/Papers/Index')
            ->where('manuscript_max_mb', \App\Support\UploadLimit::manuscriptMegabytes()));

    expect(\App\Support\UploadLimit::manuscriptMegabytes())->toBeGreaterThanOrEqual(1)
        ->toBeLessThanOrEqual((int) config('manuscripts.max_upload_mb'));
});

test('a PDF over the limit is rejected with the limit in the message', function () {
    $kb = \App\Support\UploadLimit::manuscriptKilobytes();

    $this->actingAs($this->admin)->post(route('admin.papers.store'), [
        'track_id' => $this->track->id,
        'paper_no' => 'T1-009',
        'title' => 'Huge',
        'researcher' => 'Jane Doe',
        'manuscript' => UploadedFile::fake()->create('huge.pdf', $kb + 1, 'application/pdf'),
    ])->assertRedirect()->assertSessionHasErrors(['manuscript' => 'The manuscript must not exceed ' . \App\Support\UploadLimit::manuscriptMegabytes() . ' MB.']);

    expect(Paper::where('paper_no', 'T1-009')->exists())->toBeFalse();
});

test('an upload PHP discarded for exceeding post_max_size comes back as a toast, not a 413 page', function () {
    $paper = uploadPaperWithManuscript($this);
    $tooBig = \App\Support\UploadLimit::iniBytes('post_max_size') + 1;
    if ($tooBig <= 1) {
        $this->markTestSkipped('post_max_size is unlimited in this PHP.');
    }

    $this->actingAs($this->admin)
        ->from(route('admin.papers.index'))
        ->call('POST', route('admin.papers.update', $paper), [], [], [], [
            'CONTENT_LENGTH' => $tooBig,
            'CONTENT_TYPE' => 'multipart/form-data; boundary=x',
        ])
        ->assertRedirect(route('admin.papers.index'))
        ->assertSessionHas('error', fn ($m) => str_contains($m, 'too large'));
});

test('a spoofed edit whose body was dropped (plain POST) is also explained instead of a 405 page', function () {
    $paper = uploadPaperWithManuscript($this);

    $this->actingAs($this->admin)
        ->from(route('admin.papers.index'))
        ->call('POST', route('admin.papers.update', $paper), [], [], [], [
            'CONTENT_TYPE' => 'multipart/form-data; boundary=x',
        ])
        ->assertRedirect(route('admin.papers.index'))
        ->assertSessionHas('error', fn ($m) => str_contains($m, 'too large'));
});
