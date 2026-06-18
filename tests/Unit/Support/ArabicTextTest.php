<?php

use App\Support\ArabicText;

test('forPdf leaves non-Arabic strings unchanged', function () {
    expect(ArabicText::forPdf('Hello World'))->toBe('Hello World')
        ->and(ArabicText::forPdf('CERT-ABC123'))->toBe('CERT-ABC123')
        ->and(ArabicText::forPdf(null))->toBe('')
        ->and(ArabicText::forPdf(''))->toBe('');
});

test('forPdf shapes and reorders Arabic text', function () {
    $input = 'نادي الحاسبات';
    $output = ArabicText::forPdf($input);

    // The shaped output uses Arabic presentation forms, so it differs from the
    // logical input but is not empty.
    expect($output)->not->toBe($input)
        ->and($output)->not->toBe('')
        ->and(preg_match('/\p{Arabic}/u', $output))->toBe(1);
});

test('forPdf keeps western digits in mixed Arabic strings', function () {
    expect(ArabicText::forPdf('01 يونيو 2026'))
        ->toContain('2026')
        ->toContain('01');
});

test('shapeHtml shapes text nodes but preserves tags and styles', function () {
    $html = '<p>نادي الحاسبات</p><style>.a{color:red}</style>';
    $output = ArabicText::shapeHtml($html);

    expect($output)
        ->toContain('<p>')
        ->toContain('.a{color:red}')
        ->not->toContain('نادي الحاسبات'); // the text node was reshaped
});

test('shapeHtml returns input untouched when it has no Arabic', function () {
    $html = '<p>Hello</p>';

    expect(ArabicText::shapeHtml($html))->toBe($html);
});
