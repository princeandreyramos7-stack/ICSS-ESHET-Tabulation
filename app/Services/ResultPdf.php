<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfDocument;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Renders the printable result sheets (resources/views/pdf/results/*) to PDF.
 *
 * The sheets mirror the on-screen print layout: letterhead with the three
 * seals, conference name and theme, the amber track band, the bordered score
 * table, notes and the signature blocks. Every sheet is A4 landscape and is
 * fitted to a fixed number of pages the same way the browser's print zoom
 * does: render, and if it spills over, render again a little smaller.
 *
 * Logos are embedded as data URIs so DomPDF never touches the filesystem
 * (its chroot excludes the web root on the production host) and a missing
 * image can never break a download.
 */
class ResultPdf
{
    /** Repo-relative logo files, in display order: university, conference, city. */
    private const LOGOS = [
        'university' => 'public/img/Isu_logo.jpg',
        'conference' => 'public/img/ICSSESHET.jpg',
        'city' => 'public/img/Ilagan.png',
    ];

    /** Zoom ladder tried while the sheet still needs more pages than requested. */
    private const SCALES = [1.0, 0.92, 0.85, 0.78, 0.72, 0.66, 0.6, 0.55, 0.5, 0.45, 0.4];

    /**
     * @param  int  $pages  how many printed pages the sheet has; the view wraps each
     *                      one in <div class="pg-N"> and honours $only to render one alone
     */
    public function download(string $view, array $data, string $filename, int $pages = 1): Response
    {
        $shared = $data + [
            'logos' => $this->logos(),
            'conference' => config('conference'),
            'generatedAt' => now(),
        ];

        // Each page gets the largest zoom at which it fits one sheet of paper, found on its own.
        $scales = [];
        for ($n = 1; $n <= $pages; $n++) {
            $scales[$n] = $this->fitScale($view, $shared + ['only' => $pages > 1 ? $n : null], $n);
        }

        $pdf = $this->render($view, $shared + ['scales' => $scales]);
        if ($this->pageCount($pdf) !== $pages) {
            Log::warning('Result PDF did not land on its target page count.', [
                'view' => $view,
                'pages' => $pages,
                'rendered' => $this->pageCount($pdf),
                'scales' => $scales,
            ]);
        }
        $this->stampPageNumbers($pdf);

        return $pdf->download($filename);
    }

    /**
     * Render page $n alone at full size; if it spills over, bisect the zoom ladder
     * for the largest scale at which it fits one sheet (page count is monotonic in
     * zoom, so this takes about four renders in the worst case).
     */
    private function fitScale(string $view, array $data, int $n): float
    {
        $fits = fn (float $scale) => $this->pageCount(
            $this->render($view, $data + ['scales' => [$n => $scale]])
        ) <= 1;

        if ($fits(1.0)) {
            return 1.0;
        }

        $ladder = array_values(array_filter(self::SCALES, fn ($s) => $s < 1.0)); // descending
        $best = null;
        $lo = 0;
        $hi = count($ladder) - 1;
        while ($lo <= $hi) {
            $mid = intdiv($lo + $hi, 2);
            if ($fits($ladder[$mid])) {
                $best = $ladder[$mid];
                $hi = $mid - 1; // try a larger zoom
            } else {
                $lo = $mid + 1; // need a smaller zoom
            }
        }

        return $best ?? min(self::SCALES);
    }

    private function render(string $view, array $data): PdfDocument
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4', 'landscape');
        $pdf->render();

        return $pdf;
    }

    private function pageCount(PdfDocument $pdf): int
    {
        return (int) $pdf->getDomPDF()->getCanvas()->get_page_count();
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
    private function stampPageNumbers(PdfDocument $pdf): void
    {
        try {
            $dompdf = $pdf->getDomPDF();
            $canvas = $dompdf->getCanvas();
            $metrics = $dompdf->getFontMetrics();
            $font = $metrics->getFont('DejaVu Sans');
            $size = 7;
            $text = 'Page {PAGE_NUM} of {PAGE_COUNT}';
            $width = $metrics->getTextWidth('Page 99 of 99', $font, $size);

            // 10mm side margin = 28pt; footer baseline sits 24pt above the page edge.
            $canvas->page_text($canvas->get_width() - 28 - $width, $canvas->get_height() - 24, $text, $font, $size, [0.42, 0.45, 0.50]);
        } catch (Throwable $e) {
            // Page numbers are decoration; never fail the download over them.
            Log::warning('Result PDF page numbers could not be stamped.', ['exception' => $e]);
        }
    }
}
