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

function uploadPaperWithManuscript($test, string $originalName = 'manuscript.pdf'): Paper
{
    $test->actingAs($test->admin)->post(route('admin.papers.store'), [
        'track_id' => $test->track->id,
        'paper_no' => 'T1-001',
        'title' => 'Test Paper',
        'researcher' => 'Jane Doe',
        'manuscript' => UploadedFile::fake()->create($originalName, 100, 'application/pdf'),
    ])->assertRedirect();

    return Paper::where('paper_no', 'T1-001')->firstOrFail();
}

test('admin upload stores the manuscript on the default disk', function () {
    $paper = uploadPaperWithManuscript($this);

    expect($paper->manuscript_path)->toStartWith("manuscripts/track-{$this->track->id}/");
    expect($paper->hasManuscript())->toBeTrue();
    Storage::disk('local')->assertExists($paper->manuscript_path);
});

test('evaluator on the paper track can view the manuscript inline', function () {
    $paper = uploadPaperWithManuscript($this);

    $response = $this->actingAs($this->evaluator)->get(route('manuscripts.show', $paper));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('inline');
});

test('admin can view any manuscript', function () {
    $paper = uploadPaperWithManuscript($this);

    $this->actingAs($this->admin)->get(route('manuscripts.show', $paper))->assertOk();
});

test('manuscript with a non-ascii original filename still streams', function () {
    $paper = uploadPaperWithManuscript($this, 'Peña – Résumé "final".pdf');

    $response = $this->actingAs($this->admin)->get(route('manuscripts.show', $paper));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'application/pdf');
    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toStartWith('inline;');
    // RFC 6266: ASCII fallback plus the UTF-8 encoded real name.
    expect($disposition)->toContain('filename=')->toContain("filename*=utf-8''");
});

test('evaluator from another track is forbidden', function () {
    $paper = uploadPaperWithManuscript($this);
    $outsider = User::factory()->create(['track_id' => $this->otherTrack->id]);
    $outsider->assignRole(User::ROLE_EVALUATOR);

    $this->actingAs($outsider)->get(route('manuscripts.show', $paper))->assertForbidden();
});

test('missing manuscript file returns 404 rather than 500', function () {
    $paper = Paper::create([
        'track_id' => $this->track->id,
        'paper_no' => 'T1-002',
        'title' => 'No file',
        'researcher' => 'Jane Doe',
        'manuscript_path' => 'manuscripts/track-1/does-not-exist.pdf',
        'manuscript_original_name' => 'x.pdf',
    ]);

    $this->actingAs($this->admin)->get(route('manuscripts.show', $paper))->assertNotFound();
});

test('guest is redirected to login', function () {
    $paper = uploadPaperWithManuscript($this);
    auth()->logout();

    $this->get(route('manuscripts.show', $paper))->assertRedirect(route('login'));
});
