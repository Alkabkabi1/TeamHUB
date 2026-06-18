<?php

test('home document exposes Ruwad SEO meta tags', function () {
    $response = $this->get('/')->assertOk();
    $html = $response->getContent();

    // Core SEO + Open Graph / Twitter cards are present and Ruwad-branded.
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

test('home document uses PNG favicons, not the SVG Laravel mark', function () {
    $html = $this->get('/')->assertOk()->getContent();

    expect($html)
        ->toContain('<link rel="icon" type="image/png" href="/favicon-32x32.png"')
        ->toContain('<link rel="apple-touch-icon" href="/apple-touch-icon.png">')
        ->not->toContain('favicon.svg');
});
