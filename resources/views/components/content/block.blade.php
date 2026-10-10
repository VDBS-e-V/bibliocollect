@props(['key'])

@php($html = app(\App\Modules\Content\Services\ContentBlocks::class)->render($key))

@if ($html !== null)
    <aside class="bc-content-notice" aria-label="Hinweis der Bibliothek">
        <div class="bc-content-page__body">{!! $html !!}</div>
    </aside>
@endif
