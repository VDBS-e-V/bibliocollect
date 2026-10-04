<?php

declare(strict_types=1);

it('ships every VDBS web font as a Vite source asset', function (): void {
    $css = (string) file_get_contents(resource_path('css/foundation/font-faces.css'));

    $fonts = [
        'Lato-Regular.ttf',
        'Lato-Italic.ttf',
        'Lato-Bold.ttf',
        'Lato-Black.ttf',
        'SourceSerif4-Regular.ttf',
        'SourceSerif4-Italic.ttf',
        'SourceSerif4-SemiBold.ttf',
        'NeulandFont_2017.ttf',
    ];

    foreach ($fonts as $font) {
        expect(is_file(resource_path('fonts/vdbs/'.$font)))
            ->toBeTrue("Font source asset [{$font}] is missing.");
        expect($css)->toContain("../../fonts/vdbs/{$font}");
    }

    expect($css)
        ->not->toContain('fonts.googleapis.com')
        ->not->toContain('fonts.gstatic.com')
        ->not->toContain("url('/fonts/vdbs/");
});

it('declares the expected VDBS font weights and styles', function (): void {
    $css = (string) file_get_contents(resource_path('css/foundation/font-faces.css'));

    $faces = [
        ['Lato-Regular.ttf', 'Lato', 400, 'normal'],
        ['Lato-Italic.ttf', 'Lato', 400, 'italic'],
        ['Lato-Bold.ttf', 'Lato', 700, 'normal'],
        ['Lato-Black.ttf', 'Lato', 900, 'normal'],
        ['SourceSerif4-Regular.ttf', 'Source Serif 4', 400, 'normal'],
        ['SourceSerif4-Italic.ttf', 'Source Serif 4', 400, 'italic'],
        ['SourceSerif4-SemiBold.ttf', 'Source Serif 4', 600, 'normal'],
        ['NeulandFont_2017.ttf', 'Neuland', 400, 'normal'],
    ];

    preg_match_all('/@font-face\s*\{[^}]*\}/s', $css, $matches);

    foreach ($faces as [$file, $family, $weight, $style]) {
        $blocks = array_values(array_filter(
            $matches[0],
            static fn (string $block): bool => str_contains($block, $file),
        ));

        expect($blocks)->toHaveCount(1);
        expect($blocks[0])->toContain('font-family: "'.$family.'";');
        expect($blocks[0])->toContain('font-weight: '.$weight.';');
        expect($blocks[0])->toContain('font-style: '.$style.';');
    }
});

it('keeps the UI on Lato and constrains the special type roles', function (): void {
    $tokens = (string) file_get_contents(resource_path('css/tokens.css'));
    $typography = (string) file_get_contents(resource_path('css/foundation/typography.css'));

    expect($tokens)->toContain('--font-family-base: "Lato", Arial, "Segoe UI", system-ui, sans-serif;');
    expect($tokens)->toContain('--font-family-display: "Neuland", "Lato", Arial, sans-serif;');
    expect($tokens)->toContain('--font-family-editorial: "Source Serif 4", Georgia, serif;');
    expect($typography)->toContain('body { font-family:var(--font-family-base);');
    expect($typography)->toContain('h1,h2,h3,h4,h5,h6 { margin-block-start:0; color:var(--text-primary); font-family:var(--font-family-base);');
    expect($typography)->toContain('.vdbs-display { font-family:var(--font-family-display); font-size:var(--text-4xl);');
    expect($typography)->toContain('.vdbs-editorial { font-family:var(--font-family-editorial); }');
});

it('ships the font license notices with the source assets', function (): void {
    expect(is_file(resource_path('fonts/vdbs/licenses/Lato-OFL.txt')))->toBeTrue();
    expect(is_file(resource_path('fonts/vdbs/licenses/SourceSerif4-OFL.txt')))->toBeTrue();
    expect(is_file(resource_path('fonts/vdbs/licenses/NeulandFont_2017-NOTICE.txt')))->toBeTrue();
});

it('does not keep legacy public font files, source archives or the migration helper', function (): void {
    expect(glob(public_path('fonts/vdbs/*.ttf')) ?: [])->toBe([]);
    expect(is_file(base_path('Lato,Source_Serif_4.zip')))->toBeFalse();
    expect(glob(base_path('Neuland_Font_2017*.zip')) ?: [])->toBe([]);
    expect(is_file(base_path('tools/sync_vdbs_fonts.php')))->toBeFalse();
});
