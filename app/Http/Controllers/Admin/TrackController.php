<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Track;
use App\Services\ResultService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TrackController extends Controller
{
    public function index(ResultService $results): Response
    {
        return Inertia::render('Admin/Tracks/Index', [
            'progress' => $results->progress(),
        ]);
    }

    /**
     * Lock or unlock a track. Locked tracks reject new or edited evaluations.
     */
    public function toggleLock(Request $request, Track $track): RedirectResponse
    {
        $validated = $request->validate([
            'is_locked' => ['required', 'boolean'],
        ]);

        $track->update(['is_locked' => $validated['is_locked']]);

        $state = $track->is_locked ? 'locked' : 'unlocked';

        return back()->with('success', "Track {$track->number} has been {$state}.");
    }
}
