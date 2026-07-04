<!DOCTYPE html>
@php($appearance = request()->cookie('appearance', 'light'))
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"
    class="{{ $appearance === 'dark' ? 'dark' : '' }}"
    style="color-scheme: {{ $appearance === 'dark' ? 'dark' : 'light' }}"
>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <link rel="icon" type="image/svg+xml" href="/teamhub-favicon.svg">
        <link rel="apple-touch-icon" href="/teamhub-icon.svg">
        <link rel="manifest" href="/site.webmanifest">
        <meta name="theme-color" content="{{ config('theme.brand', '#006471') }}">

        {{-- Saudi is the primary typeface; preload so it paints without waiting on the CDN. --}}
        <link rel="preload" href="/fonts/saudi/SaudiWeb-Regular.woff2" as="font" type="font/woff2" crossorigin>
        <link rel="preload" href="/fonts/saudi/SaudiWeb-Bold.woff2" as="font" type="font/woff2" crossorigin>

        {{--
            @font-face is declared here (document origin) rather than in app.css so the
            font URLs resolve against the Laravel app. In dev, app.css is served by the
            Vite dev server, whose origin does not serve files from public/.
            Files live in public/fonts/saudi; Cairo (below) and the system stack are fallbacks.
        --}}
        <style>
            @font-face {
                font-family: 'Saudi';
                src:
                    url('/fonts/saudi/SaudiWeb-Regular.woff2') format('woff2'),
                    url('/fonts/saudi/SaudiWeb-Regular.woff') format('woff');
                font-weight: 100 500;
                font-style: normal;
                font-display: swap;
            }
            @font-face {
                font-family: 'Saudi';
                src:
                    url('/fonts/saudi/SaudiWeb-Bold.woff2') format('woff2'),
                    url('/fonts/saudi/SaudiWeb-Bold.woff') format('woff');
                font-weight: 600 900;
                font-style: normal;
                font-display: swap;
            }
        </style>

        {{-- Cairo remains as a fallback typeface. --}}
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link
            href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700&display=swap"
            rel="stylesheet"
        />

        <script>
            (() => {
                try {
                    const match = document.cookie.match(
                        /(?:^|; )appearance=([^;]+)/,
                    );
                    const cookieAppearance = match
                        ? decodeURIComponent(match[1])
                        : null;
                    const storedAppearance =
                        window.localStorage.getItem('appearance') ??
                        cookieAppearance ??
                        '{{ $appearance }}';
                    const resolvedAppearance =
                        storedAppearance === 'dark' ? 'dark' : 'light';

                    document.documentElement.classList.toggle(
                        'dark',
                        resolvedAppearance === 'dark',
                    );
                    document.documentElement.style.colorScheme =
                        resolvedAppearance;
                } catch {
                    // Ignore storage access issues and fall back to the
                    // server-rendered appearance above.
                }
            })();
        </script>

        @vite(['resources/css/app.css', 'resources/js/app.ts'])

        {{-- Default TeamHUB brand color, server-rendered before any workspace override. --}}
        <style>:root{--brand: {{ config('theme.brand', '#006471') }};}</style>

        {{-- SEO defaults. This SPA is not server-rendered, so crawlers see these
             document-level tags; per-page <title> is layered on by Inertia. --}}
        @php($seoLocale = app()->getLocale() === 'ar' ? 'ar_SA' : 'en_US')
        @php($seoAltLocale = app()->getLocale() === 'ar' ? 'en_US' : 'ar_SA')
        <meta name="description" content="{{ __('seo.description') }}">
        <meta name="application-name" content="{{ config('app.name') }}">
        <link rel="canonical" href="{{ url()->current() }}">

        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ __('seo.title') }}">
        <meta property="og:description" content="{{ __('seo.description') }}">
        <meta property="og:url" content="{{ url()->current() }}">
        <meta property="og:image" content="{{ asset('images/og-image.png') }}">
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        <meta property="og:locale" content="{{ $seoLocale }}">
        <meta property="og:locale:alternate" content="{{ $seoAltLocale }}">

        <meta name="twitter:card" content="summary_large_image">
        <meta name="twitter:title" content="{{ __('seo.title') }}">
        <meta name="twitter:description" content="{{ __('seo.description') }}">
        <meta name="twitter:image" content="{{ asset('images/og-image.png') }}">

        <x-inertia::head>
            <title>{{ __('seo.title') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        @include('partials.intro-splash')
        <x-inertia::app />
    </body>
</html>
