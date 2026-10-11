@props([
    'title',
    'description' => null,
    'compact' => false,
])

<fieldset {{ $attributes->class([
    'bc-form-section',
    'bc-form-section--compact' => $compact,
]) }}>
    <legend class="bc-form-section__legend">{{ $title }}</legend>
    @if ($description)
        <p class="bc-form-section__description">{{ $description }}</p>
    @endif
    <div class="bc-form-section__body">{{ $slot }}</div>
</fieldset>
