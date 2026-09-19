<?php

namespace App\Http\Controllers;

use App\Models\Paper;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ManuscriptController extends Controller
{
    /**
     * Stream a paper's manuscript PDF inline (admins: any paper; evaluators: their own track only).
     */
    public function show(Paper $paper): Response
    {
        $user = auth()->user();

        // Evaluators sit on exactly one track (users.track_id). Cast both sides: MySQL can
        // hand back integer columns as strings, and a strict mismatch would 403 everyone.
        if (! $user->isAdmin() && (int) $paper->track_id !== (int) $user->track_id) {
            abort(403, 'You do not have access to this manuscript.');
        }

        if (! $paper->hasManuscript()) {
            abort(404, 'Manuscript not found.');
        }

        // Storage::response() streams from the configured disk and builds an ASCII-safe
        // Content-Disposition fallback, so a non-ASCII original filename cannot break the header.
        return Storage::response(
            $paper->manuscript_path,
            $paper->manuscript_original_name ?: basename($paper->manuscript_path),
            ['Content-Type' => 'application/pdf'],
            'inline'
        );
    }
}
