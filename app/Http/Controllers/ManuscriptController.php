<?php

namespace App\Http\Controllers;

use App\Models\Paper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ManuscriptController extends Controller
{
    /**
     * Display/download a paper's manuscript PDF
     */
    public function show(Paper $paper): Response
    {
        $user = auth()->user();

        // Authorization: Admin can view all, evaluators can only view their assigned tracks
        if (!$user->isAdmin()) {
            $assignedTrackIds = $user->tracks()->pluck('tracks.id')->toArray();
            
            if (!in_array($paper->track_id, $assignedTrackIds)) {
                abort(403, 'You do not have access to this manuscript.');
            }
        }

        // Check if manuscript exists
        if (!$paper->hasManuscript()) {
            abort(404, 'Manuscript not found.');
        }

        // Return PDF file
        return response()->file(
            Storage::path($paper->manuscript_path),
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $paper->manuscript_original_name . '"'
            ]
        );
    }
}