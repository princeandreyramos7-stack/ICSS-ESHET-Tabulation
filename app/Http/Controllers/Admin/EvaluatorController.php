<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluatorRequest;
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
            ->withCount(['evaluations as evaluations_count' => fn ($q) => $q->whereNotNull('submitted_at')])
            ->get();

        return Inertia::render('Admin/Evaluators/Index', [
            'evaluators' => $evaluators->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'evaluations_count' => $u->evaluations_count,
                'created_at' => $u->created_at?->toDateTimeString(),
            ])->values(),
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
