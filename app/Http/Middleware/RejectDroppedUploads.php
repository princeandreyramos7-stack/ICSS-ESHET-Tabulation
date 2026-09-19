<?php

namespace App\Http\Middleware;

use App\Support\UploadLimit;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * When a multipart upload exceeds PHP's post_max_size, PHP throws the whole
 * body away before Laravel runs: no fields, no files, not even Inertia's
 * spoofed _method. Instead of a validation wall or a 405 page, send the admin
 * back to the form with the limit that applies.
 */
class RejectDroppedUploads
{
    public function handle(Request $request, Closure $next): Response
    {
        $multipart = str_starts_with((string) $request->header('Content-Type'), 'multipart/form-data');

        if ($request->isMethod('POST') && $multipart && $request->request->count() === 0 && $request->files->count() === 0) {
            $max = UploadLimit::manuscriptMegabytes();

            return back()->with('error', "That file is too large for this server. Please upload a PDF of at most {$max} MB.");
        }

        return $next($request);
    }
}
