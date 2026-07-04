<?php

test('home document exposes TeamHUB SEO meta tags', function () {
    $response = $this->get('/')->assertOk();
    $html = $response->getContent();

    // Core SEO + Open Graph / Twitter cards are present and TeamHUB-branded.
    expect($html)
        ->toContain('<meta name="description"')
        ->toContain(__('seo.description'))
        ->toContain('<meta property="og:title" content="'.e(__('seo.title')).'">')
        ->toContain('<meta property="og:image" content="'.url('images/og-image.png').'">')
        ->toContain('<meta name="twitter:card" content="summary_large_image">')
        ->toContain('<meta name="theme-color" content="#006471">')
        ->toContain('<link rel="canonical"')
        ->toContain('<link rel="manifest" href="/site.webmanifest">');
});

test('home document uses TeamHUB favicon assets', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<link rel="icon" type="image/svg+xml" href="/teamhub-favicon.svg">')
        ->toContain('<link rel="apple-touch-icon" href="/teamhub-icon.svg">')
        ->not->toContain('/favicon-32x32.png');
});
