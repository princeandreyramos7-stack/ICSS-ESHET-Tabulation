<?php

namespace App\Http\Middleware;

use App\Models\Track;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Props shared with every page: the signed-in user (with role),
     * flash messages, and conference branding.
     */
    public function share(Request $request): array
    {
        /** @var User|null $user */
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->isAdmin() ? User::ROLE_ADMIN : ($user->isEvaluator() ? User::ROLE_EVALUATOR : null),
                    // The one track this evaluator is assigned to; null for admins and unassigned evaluators.
                    'track' => $user->track_id && ($t = $user->track)
                        ? ['id' => $t->id, 'number' => $t->number, 'name' => $t->name, 'venue' => $t->venue]
                        : null,
                ] : null,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'conference' => config('conference'),
            // Sidebar navigation needs the track list on every authenticated page.
            // Admins see every track; evaluators only their assigned one.
            'tracks' => fn () => $user
                ? Track::orderBy('number')
                    ->when(! $user->isAdmin(), fn ($q) => $q->whereKey($user->track_id))
                    ->get(['id', 'number', 'name'])
                    ->map(fn ($t) => ['id' => $t->id, 'number' => $t->number, 'name' => $t->name])
                    ->values()
                : [],
        ];
    }
}
