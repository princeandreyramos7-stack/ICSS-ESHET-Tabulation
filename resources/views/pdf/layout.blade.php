<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title') - {{ $conference['short_name'] }}</title>
    <style>
        /* Mirrors the on-screen print sheet (PrintSheetHeader, result tables, EvaluatorSignatures). */
        @page { margin: 12mm 12mm 18mm 12mm; }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9pt;
            line-height: 1.35;
            color: #111827;
        }
        p { margin: 0; }

        /* Letterhead */
        .letterhead { text-align: center; border-bottom: 2px solid #064e3b; padding-bottom: 8px; margin-bottom: 10px; }
        .seals { margin-bottom: 4px; line-height: 0; }
        .seals img { vertical-align: middle; margin: 0 5px; }
        .seal-side { width: 46px; height: 46px; }
        .seal-main { width: 60px; height: 60px; }
        .organizer { font-size: 7.5pt; letter-spacing: 2px; text-transform: uppercase; color: #6b7280; margin-top: 4px; }
        .conf-name { font-size: 13pt; font-weight: bold; color: #064e3b; line-height: 1.25; margin: 3px 0 2px; }
        .conf-theme { font-size: 8pt; font-weight: bold; letter-spacing: 1.5px; text-transform: uppercase; color: #d97706; }
        .track-band-wrap { border-collapse: collapse; margin: 7px auto 0; }
        .track-band {
            padding: 3px 12px; border-radius: 4px; text-align: center;
            background: #fbbf24; color: #022c22; font-size: 9.5pt; font-weight: bold;
        }
        .venue { font-size: 8pt; color: #4b5563; margin-top: 3px; }
        .sheet-title { font-size: 11pt; font-weight: bold; color: #1f2937; margin-top: 7px; }

        /* Meta line above a table */
        .meta { width: 100%; font-size: 8.5pt; color: #4b5563; margin-bottom: 6px; }
        .meta td { padding: 0; }
        .badge {
            display: inline-block; padding: 1px 7px; border-radius: 3px; font-size: 7.5pt; font-weight: bold;
            background: #fef3c7; color: #92400e; border: 1px solid #fcd34d;
        }
        .badge-muted { background: #f3f4f6; color: #4b5563; border-color: #e5e7eb; }

        /* Score tables */
        table.sheet { width: 100%; border-collapse: collapse; page-break-inside: auto; }
        table.sheet tr { page-break-inside: avoid; }
        table.sheet th, table.sheet td { border: 1px solid #1f2937; padding: 5px 6px; vertical-align: middle; }
        table.sheet thead th { background: #064e3b; color: #ffffff; font-weight: bold; text-align: left; }
        table.sheet thead th.center { text-align: center; }
        table.sheet th.avg-head { background: #065f46; text-align: center; }
        table.sheet th.rank-head { background: #fbbf24; color: #022c22; text-align: center; }
        table.sheet tbody tr.even td { background: #f9fafb; }
        table.sheet tbody tr.top td { background: #fffbeb; }
        table.sheet td.avg { background: #ecfdf5; color: #064e3b; font-weight: bold; text-align: center; }
        table.sheet td.rank { font-weight: bold; text-align: center; font-size: 10pt; }
        table.sheet td.num { text-align: center; }
        table.sheet tfoot td { background: #fffbeb; font-weight: bold; }
        .th-sub { display: block; font-size: 7pt; font-weight: normal; color: #a7f3d0; }
        .title { font-weight: bold; color: #111827; }
        .sub { font-size: 7.5pt; color: #4b5563; }
        .partial { display: block; font-size: 7pt; font-weight: normal; color: #b45309; }
        .paper-no { font-weight: bold; }

        /* Small "card" tables on the overall sheet */
        table.card { width: 100%; border-collapse: collapse; border: 1px solid #1f2937; }
        table.card th.card-head { background: #064e3b; color: #ffffff; text-align: left; padding: 4px 8px; border-bottom: 1px solid #1f2937; }
        table.card .card-track { display: block; font-size: 7pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; color: #fcd34d; }
        table.card .card-name { display: block; font-size: 8pt; font-weight: bold; }
        table.card thead.cols th { background: #f3f4f6; color: #374151; font-size: 7.5pt; font-weight: bold; padding: 3px 6px; border-bottom: 1px solid #d1d5db; text-align: left; }
        table.card td { font-size: 7.5pt; padding: 3px 6px; border-bottom: 1px solid #e5e7eb; }
        table.card tr.top td { background: #fffbeb; font-weight: bold; }

        .note { font-size: 7.5pt; color: #6b7280; margin-top: 6px; }
        .empty { border: 1px dashed #d1d5db; border-radius: 4px; padding: 16px; text-align: center; color: #6b7280; font-size: 8.5pt; }
        .section-title { font-size: 8.5pt; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; color: #064e3b; margin: 10px 0 5px; }

        /* Definition list (paper breakdown) */
        table.dl { border-collapse: collapse; margin-bottom: 8px; }
        table.dl td { padding: 1px 10px 1px 0; font-size: 9pt; vertical-align: top; }
        table.dl td.k { font-weight: bold; color: #6b7280; white-space: nowrap; }

        /* Comments */
        .comment { border: 1px solid #e5e7eb; background: #f9fafb; border-radius: 4px; padding: 6px 8px; margin-top: 5px; page-break-inside: avoid; }
        .comment .who { font-size: 7.5pt; font-weight: bold; color: #374151; }
        .comment .text { font-size: 8.5pt; color: #1f2937; margin-top: 2px; white-space: pre-wrap; }

        /* Signature blocks */
        .sig-heading { text-align: center; font-size: 7.5pt; font-weight: bold; letter-spacing: 2px; text-transform: uppercase; color: #4b5563; margin: 22px 0 6px; }
        table.sigs { width: 100%; border-collapse: collapse; page-break-inside: avoid; }
        table.sigs td { text-align: center; padding: 20px 8px 4px; vertical-align: top; }
        .sig-line { border-top: 1px solid #000000; width: 150px; margin: 0 auto; }
        .sig-name { font-size: 9pt; font-weight: bold; margin-top: 3px; }
        .sig-role { font-size: 7pt; color: #6b7280; }

        /* Footer strip (page number is stamped by ResultPdf at the right edge) */
        .footer { position: fixed; bottom: -11mm; left: 0; right: 0; height: 8mm; border-top: 1px solid #e5e7eb; padding-top: 3px; font-size: 7pt; color: #6b7280; text-align: left; }

        .avoid-break { page-break-inside: avoid; }
        .page-break { page-break-after: always; }
        .center { text-align: center; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div class="footer">
        {{ $conference['short_name'] }} &middot; @yield('document') &middot; Generated {{ $generatedAt->format('F j, Y \a\t g:i A') }}
    </div>

    @yield('content')
</body>
</html>
