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
            padding-bottom: 15px;
            border-bottom: 3px solid #065f46;
        }
        
        .header-logo {
            font-size: 18pt;
            font-weight: bold;
            color: #065f46;
            margin-bottom: 5px;
        }
        
        .header-subtitle {
            font-size: 11pt;
            color: #4b5563;
            margin-bottom: 3px;
        }
        
        .header-title {
            font-size: 14pt;
            font-weight: bold;
            color: #1f2937;
            margin-top: 10px;
        }
        
        .header-info {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 5px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            font-size: 9pt;
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
    </style>
</head>
<body>
    @yield('content')
</body>
</html>
