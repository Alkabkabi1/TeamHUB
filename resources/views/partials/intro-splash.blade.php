{{--
    Server-rendered intro splash.

    Rendered into the very first HTML response so it paints instantly, before any
    JS loads or Inertia hydrates (SSR-safe). Plays on every full page load
    (including refreshes). Inertia client-side navigations never re-render this
    blade, so it never replays when the user moves between pages (e.g. opening
    the dashboard) — only a real document load triggers it.
--}}
<style>
    #intro-splash {
        position: fixed;
        inset: 0;
        z-index: 2147483647;
        overflow: hidden;
        background: #000;
        opacity: 1;
        transition: opacity 1600ms cubic-bezier(0.22, 0.61, 0.36, 1);
    }

    #intro-splash.intro-exit {
        opacity: 0;
        pointer-events: none;
    }

    #intro-splash .intro-stars {
        position: absolute;
        inset: 0;
        pointer-events: none;
    }

    #intro-splash .intro-star {
        position: absolute;
        border-radius: 9999px;
        background: #fff;
        animation: intro-twinkle 4s ease-in-out infinite;
    }

    @keyframes intro-twinkle {
        0%, 100% { opacity: calc(var(--o) * 0.4); }
        50% { opacity: var(--o); }
    }

    #intro-splash .intro-glow {
        position: absolute;
        left: 50%;
        top: 38%;
        width: 900px;
        height: 900px;
        max-width: 110vw;
        max-height: 110vh;
        transform: translate(-50%, -50%);
        pointer-events: none;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.10) 0%, rgba(255, 255, 255, 0.04) 35%, rgba(255, 255, 255, 0) 65%);
    }

    #intro-splash .intro-content {
        position: absolute;
        left: 50%;
        top: 38%;
        z-index: 10;
        transform: translate(-50%, -50%);
        display: flex;
        flex-direction: column;
        align-items: center;
        width: 100%;
        max-width: 680px;
        padding: 0 1rem;
        text-align: center;
        gap: clamp(4px, 1.2vh, 14px);
    }

    #intro-splash .intro-platform {
        font-weight: 300;
        letter-spacing: 0.025em;
        color: #fff;
        font-size: clamp(18px, min(5vw, 3.4vh), 36px);
    }

    #intro-splash .intro-logo {
        user-select: none;
        object-fit: contain;
        width: auto;
        height: auto;
        max-width: min(72vw, 360px);
        max-height: clamp(160px, 38vh, 340px);
    }

    #intro-splash .intro-tagline {
        font-weight: 300;
        letter-spacing: 0.025em;
        color: #fff;
        font-size: clamp(15px, min(4.4vw, 3vh), 32px);
    }

    #intro-splash .intro-bar {
        position: absolute;
        left: 0;
        right: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.10);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
        height: clamp(64px, min(18vw, 11vh), 105px);
        bottom: clamp(60px, 11vh, 120px);
    }

    #intro-splash .intro-uqu {
        user-select: none;
        object-fit: contain;
        width: auto;
        height: auto;
        max-height: clamp(82px, min(22vw, 15vh), 145px);
        max-width: 72vw;
    }
</style>

<div id="intro-splash" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}" aria-hidden="true">
    <div class="intro-stars"></div>
    <div class="intro-glow"></div>

    <div class="intro-content">
        <p class="intro-platform">{{ __('intro.platform') }}</p>
        <img class="intro-logo" src="/images/intro/big-logo.png" alt="{{ __('intro.logo_alt') }}" draggable="false" />
        <p class="intro-tagline">{{ __('intro.tagline') }}</p>
    </div>

    <div class="intro-bar">
        <img class="intro-uqu" src="/images/intro/uqu.svg" alt="{{ __('intro.uqu_alt') }}" draggable="false" />
    </div>
</div>

<script>
    (function () {
        var el = document.getElementById('intro-splash');
        if (!el) { return; }

        // Keep the splash at its natural size regardless of the large-display
        // viewport scaling (lib/viewport-scale.ts applies `zoom` to <html>).
        // Applying the inverse zoom here makes the splash's net zoom exactly 1,
        // so its sizing and full-viewport coverage match the real screen.
        var root = document.documentElement;
        var syncCounterZoom = function () {
            var z = parseFloat(root.style.zoom || '1') || 1;
            el.style.zoom = z !== 1 ? String(1 / z) : '';
        };
        syncCounterZoom();
        var zoomObserver = new MutationObserver(syncCounterZoom);
        zoomObserver.observe(root, { attributes: true, attributeFilter: ['style'] });

        var stars = el.querySelector('.intro-stars');
        if (stars) {
            var seed = 42;
            var rand = function () {
                seed = (seed * 9301 + 49297) % 233280;
                return seed / 233280;
            };
            var frag = document.createDocumentFragment();
            for (var i = 0; i < 80; i++) {
                var s = document.createElement('span');
                var size = 1 + Math.floor(rand() * 3);
                var o = 0.25 + rand() * 0.6;
                s.className = 'intro-star';
                s.style.top = (rand() * 100) + '%';
                s.style.left = (rand() * 100) + '%';
                s.style.width = size + 'px';
                s.style.height = size + 'px';
                s.style.opacity = o;
                s.style.setProperty('--o', o);
                s.style.animationDelay = (rand() * 4) + 's';
                frag.appendChild(s);
            }
            stars.appendChild(frag);
        }

        @php
            $isLocal = app()->isLocal();
        @endphp
        // todo: make it 2800
        var VISIBLE_MS = {{ $isLocal ? 0 : 2800 }};
        // todo: make it 1600
        var FADE_MS = {{ $isLocal ? 0 : 1600 }};

        if ('scrollRestoration' in history) { history.scrollRestoration = 'manual'; }
        window.scrollTo(0, 0);

        var html = document.documentElement;
        var body = document.body;
        var prevHtml = html.style.overflow;
        var prevBody = body.style.overflow;
        html.style.overflow = 'hidden';
        body.style.overflow = 'hidden';

        window.setTimeout(function () {
            el.classList.add('intro-exit');
            html.style.overflow = prevHtml;
            body.style.overflow = prevBody;
            window.scrollTo(0, 0);
        }, VISIBLE_MS);

        window.setTimeout(function () {
            zoomObserver.disconnect();
            if (el.parentNode) { el.parentNode.removeChild(el); }
        }, VISIBLE_MS + FADE_MS);
    })();
</script>
