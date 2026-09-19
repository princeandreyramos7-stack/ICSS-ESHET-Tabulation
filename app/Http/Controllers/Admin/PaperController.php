<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaperRequest;
use App\Models\Paper;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class PaperController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $trackId = $request->integer('track');

        $papers = Paper::query()
            ->select('papers.*')
            ->with('track:id,number,name')
            ->withCount(['evaluations as evaluations_count' => fn ($q) => $q->whereNotNull('submitted_at')])
            ->when($trackId, fn ($q) => $q->where('track_id', $trackId))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('paper_no', 'like', "%{$search}%")
                        ->orWhere('title', 'like', "%{$search}%")
                        ->orWhere('researcher', 'like', "%{$search}%");
                });
            })
            ->join('tracks', 'tracks.id', '=', 'papers.track_id')
            ->orderBy('tracks.number')
            ->orderBy('papers.presentation_order')
            ->orderBy('papers.paper_no')
            ->get();

        return Inertia::render('Admin/Papers/Index', [
            'papers' => $papers->map(fn (Paper $p) => [
                'id' => $p->id,
                'track_id' => $p->track_id,
                'track_number' => $p->track->number,
                'paper_no' => $p->paper_no,
                'title' => $p->title,
                'researcher' => $p->researcher,
                'affiliation' => $p->affiliation,
                'presentation_order' => $p->presentation_order,
                'evaluations_count' => $p->evaluations_count,
                'has_manuscript' => $p->hasManuscript(),
                'manuscript_name' => $p->manuscript_original_name,
                'manuscript_url' => $p->manuscript_url,
            ])->values(),
            'tracks' => Track::orderBy('number')->get(['id', 'number', 'name'])
                ->map(fn ($t) => ['id' => $t->id, 'number' => $t->number, 'name' => $t->name, 'label' => $t->label]),
            'filters' => ['search' => $search, 'track' => $trackId ?: null],
        ]);
    }

    public function store(StorePaperRequest $request): RedirectResponse
    {
        $data = $request->validated();
        
        // Handle manuscript upload
        if ($request->hasFile('manuscript')) {
            $data = $this->handleManuscriptUpload($request, $data);
        }

        $paper = Paper::create($data);

        return back()->with('success', "Paper {$paper->paper_no} added.");
    }

    public function update(StorePaperRequest $request, Paper $paper): RedirectResponse
    {
        $data = $request->validated();
        
        // Handle manuscript upload (replace existing if present)
        if ($request->hasFile('manuscript')) {
            // Delete old manuscript if exists
            if ($paper->manuscript_path) {
                Storage::delete($paper->manuscript_path);
            }
            
            $data = $this->handleManuscriptUpload($request, $data);
        }

        $paper->update($data);

        return back()->with('success', "Paper {$paper->paper_no} updated.");
    }

    public function destroy(Paper $paper): RedirectResponse
    {
        $paperNo = $paper->paper_no;
        $paper->delete(); // evaluations cascade, manuscript deleted via model event

        return back()->with('success', "Paper {$paperNo} and its evaluations were deleted.");
    }

    /**
     * Delete manuscript file
     */
    public function deleteManuscript(Paper $paper): RedirectResponse
    {
        if ($paper->manuscript_path) {
            Storage::delete($paper->manuscript_path);
            $paper->update([
                'manuscript_path' => null,
                'manuscript_original_name' => null,
            ]);
        }

        return back()->with('success', 'Manuscript deleted.');
    }

    /**
     * Handle manuscript file upload
     */
    private function handleManuscriptUpload(StorePaperRequest $request, array $data): array
    {
        $file = $request->file('manuscript');
        $originalName = $file->getClientOriginalName();
        
        // Generate unique filename: paper-{id}-{hash}.pdf
        $hash = substr(md5($originalName . time()), 0, 8);
        $filename = 'paper-' . ($data['paper_no'] ?? 'new') . '-' . $hash . '.pdf';
        
        // Store in track subdirectory
        $trackId = $data['track_id'];
        $path = $file->storeAs("manuscripts/track-{$trackId}", $filename);
        
        $data['manuscript_path'] = $path;
        $data['manuscript_original_name'] = $originalName;
        
        return $data;
    }
}