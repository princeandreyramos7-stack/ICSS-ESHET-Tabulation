<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaperRequest;
use App\Models\Paper;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaperController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->query('search', ''));
        $trackId = $request->integer('track');

        $papers = Paper::query()
            // select() must come before withCount(), otherwise it drops the count sub-select.
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
            ])->values(),
            'tracks' => Track::orderBy('number')->get(['id', 'number', 'name'])
                ->map(fn ($t) => ['id' => $t->id, 'number' => $t->number, 'name' => $t->name, 'label' => $t->label]),
            'filters' => ['search' => $search, 'track' => $trackId ?: null],
        ]);
    }

    public function store(StorePaperRequest $request): RedirectResponse
    {
        $paper = Paper::create($request->validated());

        return back()->with('success', "Paper {$paper->paper_no} added.");
    }

    public function update(StorePaperRequest $request, Paper $paper): RedirectResponse
    {
        $paper->update($request->validated());

        return back()->with('success', "Paper {$paper->paper_no} updated.");
    }

    public function destroy(Paper $paper): RedirectResponse
    {
        $paperNo = $paper->paper_no;
        $paper->delete(); // evaluations cascade

        return back()->with('success', "Paper {$paperNo} and its evaluations were deleted.");
    }
}
