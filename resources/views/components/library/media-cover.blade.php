@props([
    'src' => null,
    'title' => '',
    'author' => '',
    'size' => 'normal',
])

@php
    // Vier konsistente Cover-Illustrationen; derselbe Titel erhält immer dieselbe Variante.
    // Keine externen Requests und keine zusätzlichen Bilddateien notwendig.
    $safeTitle = trim((string) $title) !== '' ? trim((string) $title) : 'Ohne Titel';
    $palette = ['plum', 'mint', 'ink', 'sand'][hexdec(substr(hash('sha256', mb_strtolower($safeTitle)), 0, 2)) % 4];
    $hasCover = filled($src) && ! str_contains((string) $src, 'catalog-cover-placeholder.svg');
@endphp

<span {{ $attributes->class(['bc-library-cover', 'bc-library-cover--'.$size]) }}>
    @if ($hasCover)
        <img class="bc-library-cover__image" src="{{ $src }}" alt="" loading="lazy">
    @else
        {{-- Der sichtbare Titel wird direkt neben dem Cover nochmals semantisch ausgegeben. --}}
        <span class="bc-library-cover__art bc-library-cover__art--{{ $palette }}" aria-hidden="true">
            <span class="bc-library-cover__brand">BIBLIOCOLLECT · VDBS</span>
            <span class="bc-library-cover__arc"></span>
            <span class="bc-library-cover__arc bc-library-cover__arc--inner"></span>
            <span class="bc-library-cover__text">
                <strong class="bc-library-cover__title">{{ $safeTitle }}</strong>
                @if (filled($author))
                    <span class="bc-library-cover__author">{{ $author }}</span>
                @endif
            </span>
        </span>
    @endif
</span>
