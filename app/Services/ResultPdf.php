<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Renders the printable result sheets (resources/views/pdf/results/*) to PDF.
 *
 * The sheets mirror the on-screen print layout: letterhead with the three
 * seals, conference name and theme, the amber track band, the bordered score
 * table, notes and the signature blocks. Logos are embedded as data URIs so
 * DomPDF never has to touch the filesystem (its chroot excludes the web root
 * on the production host) and a missing image can never break a download.
 */
class ResultPdf
{
    /** Repo-relative logo files, in display order: university, conference, city. */
    private const LOGOS = [
        'university' => 'public/img/Isu_logo.jpg',
        'conference' => 'public/img/ICSSESHET.jpg',
        'city' => 'public/img/Ilagan.png',
    ];

    public function download(string $view, array $data, string $filename, string $orientation = 'portrait'): Response
    {
        $pdf = Pdf::loadView($view, $data + [
            'logos' => $this->logos(),
            'conference' => config('conference'),
            'generatedAt' => now(),
        ])->setPaper('a4', $orientation);

        $pdf->render();
        $this->stampPageNumbers($pdf);

        return $pdf->download($filename);
    }

    /** @return array<string, string|null> data URIs keyed by logo name; null when the file is unavailable */
    public function logos(): array
    {
        $out = [];
        foreach (self::LOGOS as $key => $relative) {
            $out[$key] = $this->dataUri(base_path($relative));
        }

        return $out;
    }

    private function dataUri(string $path): ?string
    {
        try {
            if (! is_file($path) || ! is_readable($path)) {
                return null;
            }
            $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                default => null,
            };
            $bytes = $mime ? file_get_contents($path) : false;

            return $bytes === false ? null : "data:{$mime};base64," . base64_encode($bytes);
        } catch (Throwable $e) {
            Log::warning('Result PDF logo could not be embedded.', ['path' => $path, 'exception' => $e]);

            return null;
        }
    }

    /** "Page X of Y" bottom-right, inside the footer strip drawn by the layout. */
    private function stampPageNumbers(\Barryvdh\DomPDF\PDF $pdf): void
    {
        try {
            $dompdf = $pdf->getDomPDF();
            $canvas = $dompdf->getCanvas();
            $metrics = $dompdf->getFontMetrics();
            $font = $metrics->getFont('DejaVu Sans');
            $size = 7;
            $text = 'Page {PAGE_NUM} of {PAGE_COUNT}';
            $width = $metrics->getTextWidth('Page 99 of 99', $font, $size);

            // 12mm side margin = 34pt; footer baseline sits 30pt above the page edge.
            $canvas->page_text($canvas->get_width() - 34 - $width, $canvas->get_height() - 30, $text, $font, $size, [0.42, 0.45, 0.50]);
        } catch (Throwable $e) {
            // Page numbers are decoration; never fail the download over them.
            Log::warning('Result PDF page numbers could not be stamped.', ['exception' => $e]);
        }
    }
}
