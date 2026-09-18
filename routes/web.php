<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\EvaluatorController;
use App\Http\Controllers\Admin\PaperController;
use App\Http\Controllers\Admin\ResultController;
use App\Http\Controllers\Admin\TrackController as AdminTrackController;
use App\Http\Controllers\Evaluator\DashboardController as EvaluatorDashboardController;
use App\Http\Controllers\Evaluator\EvaluationController;
use App\Http\Controllers\Evaluator\WorkspaceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WelcomeController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Entry points
|--------------------------------------------------------------------------
*/

Route::get('/', WelcomeController::class)->name('welcome');

// Role-aware landing page. Breeze and tests reference this name.
Route::get('/dashboard', function () {
    /** @var User $user */
    $user = Auth::user();

    if ($user->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    if ($user->isEvaluator()) {
        return redirect()->route('evaluator.dashboard');
    }

    Auth::logout();

    return redirect()->route('login')->withErrors([
        'email' => 'Your account has no role assigned. Please contact the administrator.',
    ]);
})->middleware('auth')->name('dashboard');

/*
|--------------------------------------------------------------------------
| Administrator
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:' . User::ROLE_ADMIN])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('dashboard', AdminDashboardController::class)->name('dashboard');

        Route::get('papers', [PaperController::class, 'index'])->name('papers.index');
        Route::post('papers', [PaperController::class, 'store'])->name('papers.store');
        Route::put('papers/{paper}', [PaperController::class, 'update'])->name('papers.update');
        Route::delete('papers/{paper}', [PaperController::class, 'destroy'])->name('papers.destroy');

        Route::get('evaluators', [EvaluatorController::class, 'index'])->name('evaluators.index');
        Route::post('evaluators', [EvaluatorController::class, 'store'])->name('evaluators.store');
        Route::put('evaluators/{evaluator}', [EvaluatorController::class, 'update'])->name('evaluators.update');
        Route::delete('evaluators/{evaluator}', [EvaluatorController::class, 'destroy'])->name('evaluators.destroy');

        Route::get('tracks', [AdminTrackController::class, 'index'])->name('tracks.index');
        Route::patch('tracks/{track}/lock', [AdminTrackController::class, 'toggleLock'])->name('tracks.lock');

        Route::get('results/overall', [ResultController::class, 'overall'])->name('results.overall');
        Route::get('results/tracks/{track}', [ResultController::class, 'track'])->name('results.track');
        Route::get('results/papers/{paper}', [ResultController::class, 'paper'])->name('results.paper');
    });

/*
|--------------------------------------------------------------------------
| Evaluator (panel member)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:' . User::ROLE_EVALUATOR])
    ->prefix('evaluator')
    ->name('evaluator.')
    ->group(function () {
        Route::get('dashboard', EvaluatorDashboardController::class)->name('dashboard');
        // Single-page scoring workspace; ?track=&paper= select the position.
        Route::get('evaluate', WorkspaceController::class)->name('workspace');
        Route::post('papers/{paper}/evaluate', [EvaluationController::class, 'store'])
            ->middleware('throttle:60,1')
            ->name('papers.evaluate.store');
    });

/*
|--------------------------------------------------------------------------
| Account
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__ . '/auth.php';
