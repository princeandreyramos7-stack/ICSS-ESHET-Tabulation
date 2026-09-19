<?php

namespace App\Http\Controllers;

use App\Models\Paper;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Serves uploaded manuscript PDFs.
 *
 * Deliberately avoids Flysystem for the read path: the file is located by
 * absolute path and streamed with plain PHP so the only things that can fail
 * are "not authorised" (403) or "file missing / unreadable" (404). Anything
 * unexpected is logged with context and still answered with a 404, never a 500.
 */
class ManuscriptController extends Controller
{
    /** Open in the browser (iframe preview or full-tab view). */
    public function show(Request $request, Paper $paper): StreamedResponse
    {
        return $this->stream($request, $paper, 'inline');
    }

    /** Force a "Save as" download. */
    public function download(Request $request, Paper $paper): StreamedResponse
    {
        return $this->stream($request, $paper, 'attachment');
    }

    private function stream(Request $request, Paper $paper, string $disposition): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $this->canView($user, $paper)) {
            abort(403, 'You do not have access to this manuscript.');
        }

        $absolutePath = $paper->manuscriptAbsolutePath();

        if ($absolutePath === null) {
            abort(404, 'This paper has no manuscript uploaded.');
        }

        try {
            $size = filesize($absolutePath);
            $headers = [
                'Content-Type' => 'application/pdf',
                'Content-Length' => $size === false ? null : (string) $size,
                'Content-Disposition' => $this->contentDisposition($disposition, $paper),
                'Cache-Control' => 'private, max-age=0, must-revalidate',
                'X-Content-Type-Options' => 'nosniff',
            ];
        } catch (Throwable $e) {
            Log::error('Manuscript could not be prepared for streaming.', [
                'paper_id' => $paper->id,
                'path' => $paper->manuscript_path,
                'exception' => $e,
            ]);
            abort(404, 'The manuscript file could not be read.');
        }

        return new StreamedResponse(function () use ($absolutePath) {
            $handle = @fopen($absolutePath, 'rb');
            if ($handle === false) {
                return;
            }
            while (! feof($handle)) {
                echo fread($handle, 1024 * 1024);
                flush();
            }
            fclose($handle);
        }, 200, array_filter($headers, fn ($v) => $v !== null));
    }

    /** Admins see every manuscript; evaluators only those on their own track. */
    private function canView(User $user, Paper $paper): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $user->track_id !== null && (int) $user->track_id === (int) $paper->track_id;
    }

    /**
     * RFC 6266 header with a pure-ASCII fallback. The original upload name can
     * contain anything (accents, quotes, dashes) and must never break the header.
     */
    private function contentDisposition(string $disposition, Paper $paper): string
    {
        $name = $paper->manuscriptDisplayName();

        $fallback = preg_replace('/[^A-Za-z0-9._ -]+/', '_', $name) ?: 'manuscript.pdf';
        $fallback = trim($fallback, '_ ') ?: 'manuscript.pdf';
        if (! str_ends_with(strtolower($fallback), '.pdf')) {
            $fallback .= '.pdf';
        }

        try {
            return HeaderUtils::makeDisposition($disposition, $name, $fallback);
        } catch (Throwable) {
            return HeaderUtils::makeDisposition($disposition, $fallback, $fallback);
        }
    }
}
