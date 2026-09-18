<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluatorRequest;
use App\Models\Track;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class EvaluatorController extends Controller
{
    public function index(): Response
    {
        $evaluators = User::evaluators()
            ->with('track:id,number,name')
            ->withCount(['evaluations as evaluations_count' => fn ($q) => $q->whereNotNull('submitted_at')])
            ->get()
            // Group the panel by track on screen (stable sort keeps the name order within a track).
            ->sortBy(fn (User $u) => $u->track?->number ?? PHP_INT_MAX)
            ->values();

        return Inertia::render('Admin/Evaluators/Index', [
            'evaluators' => $evaluators->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'track_id' => $u->track_id,
                'track_number' => $u->track?->number,
                'track_name' => $u->track?->name,
                'evaluations_count' => $u->evaluations_count,
                'created_at' => $u->created_at?->toDateTimeString(),
            ])->values(),
            'tracks' => Track::orderBy('number')->get(['id', 'number', 'name'])
                ->map(fn (Track $t) => ['id' => $t->id, 'number' => $t->number, 'name' => $t->name, 'label' => $t->label])
                ->values(),
        ]);
    }

    public function store(StoreEvaluatorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'track_id' => $data['track_id'],
                'email_verified_at' => now(),
            ]);
            $user->assignRole(User::ROLE_EVALUATOR);
        });

        return back()->with('success', "Evaluator {$data['name']} added.");
    }

    public function update(StoreEvaluatorRequest $request, User $evaluator): RedirectResponse
    {
        abort_unless($evaluator->isEvaluator(), 404);

        $data = $request->validated();

        $evaluator->name = $data['name'];
        $evaluator->email = $data['email'];
        $evaluator->track_id = $data['track_id'];
        if (! empty($data['password'])) {
            $evaluator->password = $data['password'];
        }
        $evaluator->save();

        return back()->with('success', "Evaluator {$evaluator->name} updated.");
    }

    public function destroy(User $evaluator): RedirectResponse
    {
        abort_unless($evaluator->isEvaluator(), 404);

        $name = $evaluator->name;
        $evaluator->delete(); // evaluations cascade

        return back()->with('success', "Evaluator {$name} and their evaluations were deleted.");
    }
}
