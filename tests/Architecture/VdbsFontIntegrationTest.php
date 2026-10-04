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

it('ships the font license notices with the source assets', function (): void {
    expect(is_file(resource_path('fonts/vdbs/licenses/Lato-OFL.txt')))->toBeTrue();
    expect(is_file(resource_path('fonts/vdbs/licenses/SourceSerif4-OFL.txt')))->toBeTrue();
    expect(is_file(resource_path('fonts/vdbs/licenses/NeulandFont_2017-NOTICE.txt')))->toBeTrue();
});

it('does not keep legacy public font files or original source archives', function (): void {
    expect(glob(public_path('fonts/vdbs/*.ttf')) ?: [])->toBe([]);
    expect(is_file(base_path('Lato,Source_Serif_4.zip')))->toBeFalse();
    expect(glob(base_path('Neuland_Font_2017*.zip')) ?: [])->toBe([]);
});
