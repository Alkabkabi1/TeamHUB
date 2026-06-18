<!DOCTYPE html>
<html lang="{{ $locale }}" dir="{{ $locale === 'ar' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <title>@yield('title')</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #111;
            margin: 24px;
            direction: {{ $locale === 'ar' ? 'rtl' : 'ltr' }};
            text-align: {{ $locale === 'ar' ? 'right' : 'left' }};
        }
        h1 {
            font-size: 18px;
            margin: 0 0 8px;
            color: #006471;
        }
        .meta {
            margin-bottom: 16px;
            line-height: 1.6;
        }
        .meta p {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: {{ $locale === 'ar' ? 'right' : 'left' }};
        }
        th {
            background: #f0f9fa;
            color: #006471;
        }
        .summary {
            margin-top: 12px;
            font-weight: bold;
        }
        .empty {
            margin-top: 16px;
            color: #666;
        }
        .event-block {
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .event-block h2 {
            font-size: 14px;
            margin: 0 0 6px;
            color: #006471;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #888;
        }
    </style>
</head>
<body>
    @yield('content')
    <div class="footer">{{ __('reports.footer') }}</div>
</body>
</html>
