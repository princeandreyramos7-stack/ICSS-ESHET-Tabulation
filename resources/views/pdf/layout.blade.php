<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - {{ config('conference.short_name') }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.4;
            color: #1f2937;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding: 15px;
            background: linear-gradient(135deg, #065f46 0%, #047857 100%);
            color: white;
            border-radius: 8px;
        }
        
        .header-logo {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .header-subtitle {
            font-size: 10pt;
            margin-bottom: 8px;
            opacity: 0.95;
        }
        
        .header-title {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 10px;
            background-color: #f59e0b;
            color: #1f2937;
            padding: 8px 15px;
            display: inline-block;
            border-radius: 6px;
        }
        
        .header-info {
            font-size: 9pt;
            margin-top: 8px;
            opacity: 0.9;
        }
        
        .track-badge {
            background-color: #f59e0b;
            color: #1f2937;
            padding: 6px 12px;
            font-weight: bold;
            font-size: 10pt;
            display: inline-block;
            border-radius: 4px;
            margin-bottom: 15px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9pt;
            page-break-inside: avoid;
        }
        
        table th {
            background-color: #065f46;
            color: white;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #064e3b;
        }
        
        table td {
            padding: 6px;
            border: 1px solid #d1d5db;
        }
        
        table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        
        table tr.top-row {
            background-color: #fef3c7 !important;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .font-bold {
            font-weight: bold;
        }
        
        .text-sm {
            font-size: 8pt;
        }
        
        .text-xs {
            font-size: 7pt;
        }
        
        .text-gray {
            color: #6b7280;
        }
        
        .badge {
            display: inline-block;
            padding: 2px 8px;
            background-color: #fbbf24;
            color: #065f46;
            border-radius: 4px;
            font-size: 8pt;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 2px solid #e5e7eb;
            font-size: 8pt;
            color: #6b7280;
        }
        
        .signatures {
            margin-top: 40px;
            page-break-inside: avoid;
        }
        
        .signature-grid {
            display: table;
            width: 100%;
            margin-top: 20px;
        }
        
        .signature-item {
            display: table-cell;
            width: 33%;
            padding: 0 10px;
            vertical-align: top;
        }
        
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            padding-top: 5px;
            font-size: 9pt;
            text-align: center;
        }
        
        .signature-label {
            font-size: 7pt;
            color: #6b7280;
            text-align: center;
        }
        
        /* Page break controls */
        .page-break-before {
            page-break-before: always;
        }
        
        .page-break-after {
            page-break-after: always;
        }
        
        .no-page-break {
            page-break-inside: avoid;
        }
        
        /* Responsive adjustments for different paper sizes */
        @media print {
            body {
                margin: 0;
                padding: 0;
            }
        }
        
        /* Mobile responsive - for viewing PDF on mobile devices */
        @media screen and (max-width: 768px) {
            body {
                font-size: 12pt;
            }
            table {
                font-size: 10pt;
            }
            .header-logo {
                font-size: 20pt;
            }
            .header-title {
                font-size: 16pt;
            }
        }
    </style>
</head>
<body>
    @yield('content')
</body>
</html>