<?php

namespace App\Http\Controllers\Evaluator;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEvaluationRequest;
use App\Models\Criterion;
use App\Models\Evaluation;
use App\Models\Paper;
use App\Models\Track;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class EvaluationController extends Controller
{
    /**
     * Create or update this evaluator's evaluation of the paper.
     * Idempotent: re-submitting replaces the previous ratings.
     * Redirects back to the workspace positioned on the next pending paper.
     */
    public function store(StoreEvaluationRequest $request, Paper $paper): RedirectResponse
    {
        // Evaluators may only score papers in their own track.
        abort_if((int) $paper->track_id !== (int) $request->user()->track_id, 403);

        $userId = $request->user()->id;
        $validated = $request->validated();
        $criteriaIds = Criterion::ordered()->pluck('id');

        // Total is computed server-side; the client value is never trusted.
        $total = round($criteriaIds->sum(fn ($id) => (float) $validated['scores'][$id]), 2);

        try {
            $saved = $this->persist($paper, $userId, $validated, $criteriaIds, $total);
        } catch (QueryException $e) {
            // Two identical submits racing on the (paper_id, user_id) unique index:
            // the loser retries once and updates the row the winner created.
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }
            $saved = $this->persist($paper, $userId, $validated, $criteriaIds, $total);
        }

        if (! $saved) {
            return back()->with('error', 'This track has been locked by the administrator. Scores can no longer be changed.');
        }

        return redirect()
            ->route('evaluator.workspace', [
                'track' => $paper->track_id,
                'paper' => $this->nextPaperId($paper, $userId),
            ])
            ->with('success', "Paper {$paper->paper_no} saved. Total: {$total} / 100.");
    }

    /**
     * Writes the evaluation atomically. Returns false if the track is locked,
     * checked inside the transaction so a lock applied a moment earlier wins.
     */
    protected function persist(Paper $paper, int $userId, array $validated, $criteriaIds, float $total): bool
    {
        return DB::transaction(function () use ($paper, $userId, $validated, $criteriaIds, $total) {
            $track = Track::query()->lockForUpdate()->find($paper->track_id);
            if (! $track || $track->is_locked) {
                return false;
            }

            $evaluation = Evaluation::updateOrCreate(
                ['paper_id' => $paper->id, 'user_id' => $userId],
                [
                    'total' => $total,
                    'comments' => $validated['comments'] ?? null,
                    'submitted_at' => now(),
                ]
            );

            foreach ($criteriaIds as $id) {
                $evaluation->scores()->updateOrCreate(
                    ['criterion_id' => $id],
                    ['score' => round((float) $validated['scores'][$id], 2)]
                );
            }

            return true;
        });
    }

    protected function isUniqueViolation(QueryException $e): bool
    {
        $code = (string) $e->getCode();
        $message = strtolower($e->getMessage());

        return $code === '23000' || $code === '23505'
            || str_contains($message, 'unique constraint')
            || str_contains($message, 'duplicate entry');
    }

    /**
     * The next paper in the same track that this evaluator has not yet
     * submitted, wrapping around to the start. Falls back to the paper just saved.
     */
    protected function nextPaperId(Paper $paper, int $userId): int
    {
        $ordered = Paper::where('track_id', $paper->track_id)
            ->orderBy('presentation_order')
            ->orderBy('paper_no')
            ->pluck('id')
            ->values();

        $done = Evaluation::where('user_id', $userId)
            ->whereNotNull('submitted_at')
            ->pluck('paper_id')
            ->flip();

        $start = (int) $ordered->search($paper->id);
        $count = $ordered->count();

        for ($i = 1; $i < $count; $i++) {
            $candidate = $ordered[($start + $i) % $count];
            if (! $done->has($candidate)) {
                return $candidate;
            }
        }

        return $paper->id;
    }
}
