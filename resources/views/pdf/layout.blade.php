@php
    // Each printed page of a sheet is wrapped in <div class="pg-N"> and gets its own zoom
    // ($scales[N], 0.4 - 1.0), chosen by App\Services\ResultPdf so that page fits exactly one
    // sheet of paper. The stylesheet below is emitted once per page with every size multiplied
    // by that page's scale, exactly like the browser's print zoom.
    $scales = $scales ?? [1 => 1.0];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title') - {{ $conference['short_name'] }}</title>
    <style>
        /* Mirrors the on-screen print sheet (PrintSheetHeader, result tables, EvaluatorSignatures). */
        @page { margin: 9mm 10mm 13mm 10mm; }

        * { box-sizing: border-box; }
        body { margin: 0; font-family: 'DejaVu Sans', sans-serif; line-height: 1.35; color: #111827; }
        p { margin: 0; }

        /* Footer strip (page number is stamped by ResultPdf at the right edge); never scaled. */
        .footer { position: fixed; bottom: -9mm; left: 0; right: 0; height: 7mm; border-top: 1px solid #e5e7eb; padding-top: 2px; font-size: 7pt; color: #6b7280; text-align: left; }

        .avoid-break { page-break-inside: avoid; }
        .page-break { page-break-before: always; }
        .center { text-align: center; }
        .right { text-align: right; }

@foreach($scales as $n => $s)
@php
    $g = ".pg-{$n}";
    $pt = fn ($v) => round($v * $s, 2) . 'pt';
    $px = fn ($v) => round($v * $s, 1) . 'px';
@endphp
        /* ---- page {{ $n }} at zoom {{ $s }} ---- */
        {{ $g }} { font-size: {{ $pt(9) }}; }

        /* Letterhead */
        {{ $g }} .letterhead { text-align: center; border-bottom: {{ $px(2) }} solid #064e3b; padding-bottom: {{ $px(8) }}; margin-bottom: {{ $px(10) }}; }
        {{ $g }} .seals { margin-bottom: {{ $px(4) }}; line-height: 0; }
        {{ $g }} .seals img { vertical-align: middle; margin: 0 {{ $px(5) }}; }
        {{ $g }} .seal-side { width: {{ $px(46) }}; height: {{ $px(46) }}; }
        {{ $g }} .seal-main { width: {{ $px(60) }}; height: {{ $px(60) }}; }
        {{ $g }} .organizer { font-size: {{ $pt(7.5) }}; letter-spacing: {{ $px(2) }}; text-transform: uppercase; color: #6b7280; margin-top: {{ $px(4) }}; }
        {{ $g }} .conf-name { font-size: {{ $pt(13) }}; font-weight: bold; color: #064e3b; line-height: 1.25; margin: {{ $px(3) }} 0 {{ $px(2) }}; }
        {{ $g }} .conf-theme { font-size: {{ $pt(8) }}; font-weight: bold; letter-spacing: {{ $px(1.5) }}; text-transform: uppercase; color: #d97706; }
        {{ $g }} .track-band-wrap { border-collapse: collapse; margin: {{ $px(7) }} auto 0; }
        {{ $g }} .track-band {
            padding: {{ $px(3) }} {{ $px(12) }}; border-radius: {{ $px(4) }}; text-align: center;
            background: #fbbf24; color: #022c22; font-size: {{ $pt(9.5) }}; font-weight: bold;
        }
        {{ $g }} .venue { font-size: {{ $pt(8) }}; color: #4b5563; margin-top: {{ $px(3) }}; }
        {{ $g }} .sheet-title { font-size: {{ $pt(11) }}; font-weight: bold; color: #1f2937; margin-top: {{ $px(7) }}; }

        /* Slim running header used on continuation pages */
        {{ $g }} .page-head { border-bottom: 1px solid #064e3b; padding-bottom: {{ $px(4) }}; margin-bottom: {{ $px(8) }}; width: 100%; }
        {{ $g }} .page-head td { padding: 0; font-size: {{ $pt(8) }}; color: #4b5563; }
        {{ $g }} .page-head .lead { font-size: {{ $pt(10) }}; font-weight: bold; color: #064e3b; }

        /* Meta line above a table */
        {{ $g }} .meta { width: 100%; font-size: {{ $pt(8.5) }}; color: #4b5563; margin-bottom: {{ $px(6) }}; }
        {{ $g }} .meta td { padding: 0; }
        {{ $g }} .badge {
            display: inline-block; padding: {{ $px(1) }} {{ $px(7) }}; border-radius: {{ $px(3) }}; font-size: {{ $pt(7.5) }}; font-weight: bold;
            background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;
        }
        {{ $g }} .badge-muted { background: #f3f4f6; color: #4b5563; border-color: #e5e7eb; }

        /* Score tables */
        {{ $g }} table.sheet { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        {{ $g }} table.sheet tr { page-break-inside: avoid; }
        {{ $g }} table.sheet th, {{ $g }} table.sheet td { border: 1px solid #1f2937; padding: {{ $px(5) }} {{ $px(6) }}; vertical-align: middle; }
        {{ $g }} table.sheet thead th { background: #064e3b; color: #ffffff; font-weight: bold; text-align: left; }
        {{ $g }} table.sheet thead th.center { text-align: center; }
        {{ $g }} table.sheet th.avg-head { background: #065f46; text-align: center; }
        {{ $g }} table.sheet th.rank-head { background: #fbbf24; color: #022c22; text-align: center; }
        {{ $g }} table.sheet tbody tr.even td { background: #f9fafb; }
        {{ $g }} table.sheet tbody tr.top td { background: #fffbeb; }
        {{ $g }} table.sheet td.avg { background: #ecfdf5; color: #064e3b; font-weight: bold; text-align: center; }
        {{ $g }} table.sheet td.rank { font-weight: bold; text-align: center; font-size: {{ $pt(10) }}; }
        {{ $g }} table.sheet td.num { text-align: center; }
        {{ $g }} table.sheet tfoot td { background: #fffbeb; font-weight: bold; }
        {{ $g }} .th-sub { display: block; font-size: {{ $pt(7) }}; font-weight: normal; color: #a7f3d0; }
        {{ $g }} .title { font-weight: bold; color: #111827; }
        {{ $g }} .sub { font-size: {{ $pt(7.5) }}; color: #4b5563; }
        {{ $g }} .partial { display: block; font-size: {{ $pt(7) }}; font-weight: normal; color: #b45309; }
        {{ $g }} .paper-no { font-weight: bold; }

        /* Small "card" tables on the overall sheet */
        {{ $g }} table.card { width: 100%; border-collapse: collapse; border: 1px solid #1f2937; }
        {{ $g }} table.card th.card-head { background: #064e3b; color: #ffffff; text-align: left; padding: {{ $px(4) }} {{ $px(8) }}; border-bottom: 1px solid #1f2937; }
        {{ $g }} table.card .card-track { display: block; font-size: {{ $pt(7) }}; font-weight: bold; letter-spacing: {{ $px(1) }}; text-transform: uppercase; color: #fcd34d; }
        {{ $g }} table.card .card-name { display: block; font-size: {{ $pt(8) }}; font-weight: bold; }
        {{ $g }} table.card thead.cols th { background: #f3f4f6; color: #374151; font-size: {{ $pt(7.5) }}; font-weight: bold; padding: {{ $px(3) }} {{ $px(6) }}; border-bottom: 1px solid #d1d5db; text-align: left; }
        {{ $g }} table.card td { font-size: {{ $pt(7.5) }}; padding: {{ $px(3) }} {{ $px(6) }}; border-bottom: 1px solid #e5e7eb; }
        {{ $g }} table.card tr.top td { background: #fffbeb; font-weight: bold; }
        {{ $g }} td.card-cell { vertical-align: top; padding: 0 {{ $px(6) }} {{ $px(6) }} 0; }
        {{ $g }} td.card-cell.last { padding-right: 0; }

        {{ $g }} .note { font-size: {{ $pt(7.5) }}; color: #6b7280; margin-top: {{ $px(6) }}; }
        {{ $g }} .empty { border: 1px dashed #d1d5db; border-radius: {{ $px(4) }}; padding: {{ $px(16) }}; text-align: center; color: #6b7280; font-size: {{ $pt(8.5) }}; }
        {{ $g }} .section-title { font-size: {{ $pt(8.5) }}; font-weight: bold; letter-spacing: {{ $px(1) }}; text-transform: uppercase; color: #064e3b; margin: {{ $px(10) }} 0 {{ $px(5) }}; }

        /* Definition list (paper breakdown) */
        {{ $g }} table.dl { border-collapse: collapse; margin-bottom: {{ $px(8) }}; }
        {{ $g }} table.dl td { padding: {{ $px(1) }} {{ $px(10) }} {{ $px(1) }} 0; font-size: {{ $pt(9) }}; vertical-align: top; }
        {{ $g }} table.dl td.k { font-weight: bold; color: #6b7280; white-space: nowrap; }

        /* Comments */
        {{ $g }} .comment { border: 1px solid #e5e7eb; background: #f9fafb; border-radius: {{ $px(4) }}; padding: {{ $px(6) }} {{ $px(8) }}; margin-top: {{ $px(5) }}; page-break-inside: avoid; }
        {{ $g }} .comment .who { font-size: {{ $pt(7.5) }}; font-weight: bold; color: #374151; }
        {{ $g }} .comment .text { font-size: {{ $pt(8.5) }}; color: #1f2937; margin-top: {{ $px(2) }}; white-space: pre-wrap; }

        /* Signature blocks */
        {{ $g }} .sig-heading { text-align: center; font-size: {{ $pt(7.5) }}; font-weight: bold; letter-spacing: {{ $px(2) }}; text-transform: uppercase; color: #4b5563; margin: {{ $px(18) }} 0 {{ $px(4) }}; }
        {{ $g }} table.sigs { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        {{ $g }} table.sigs td { text-align: center; padding: {{ $px(18) }} {{ $px(8) }} {{ $px(2) }}; vertical-align: top; }
        {{ $g }} .sig-line { border-top: 1px solid #000000; width: {{ $px(150) }}; margin: 0 auto; }
        {{ $g }} .sig-name { font-size: {{ $pt(9) }}; font-weight: bold; margin-top: {{ $px(3) }}; }
        {{ $g }} .sig-role { font-size: {{ $pt(7) }}; color: #6b7280; }
@endforeach
    </style>
</head>
<body>
    <div class="footer">
        {{ $conference['short_name'] }} &middot; @yield('document') &middot; Generated {{ $generatedAt->format('F j, Y \a\t g:i A') }}
    </div>

    @yield('content')
</body>
</html>
