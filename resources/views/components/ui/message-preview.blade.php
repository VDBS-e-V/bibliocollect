@props([
    'recipient',
    'subject',
    'eyebrow' => 'Nachrichtenvorschau',
    'compact' => false,
])

<article {{ $attributes->class([
    'bc-message-preview',
    'bc-message-preview--compact' => $compact,
]) }}>
    <header class="bc-message-preview__header">
        <span class="bc-message-preview__eyebrow">{{ $eyebrow }}</span>
        <dl class="bc-message-preview__meta">
            <div><dt>An</dt><dd>{{ $recipient }}</dd></div>
            <div><dt>Betreff</dt><dd>{{ $subject }}</dd></div>
        </dl>
    </header>
    <div class="bc-message-preview__body">
        {{ $slot }}
    </div>
</article>
