<?php

namespace App\Http\Controllers;

use App\Models\Criterion;
use App\Models\Track;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public landing page. Signed-in users go straight to their dashboard.
 * Only non-sensitive reference data (track names, rubric weights) is exposed.
 */
class WelcomeController extends Controller
{
    public function __invoke(): Response|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        return Inertia::render('Welcome', [
            'tracks' => Track::orderBy('number')->get(['number', 'name'])
                ->map(fn (Track $t) => ['number' => $t->number, 'name' => $t->name])
                ->values(),
            'criteria' => Criterion::ordered()->get(['name', 'weight'])
                ->map(fn (Criterion $c) => ['name' => $c->name, 'weight' => $c->weight])
                ->values(),
        ]);
    }
}
