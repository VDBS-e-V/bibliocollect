@props([
    'title',
    'open' => false,
    'compact' => false,
])

<details {{ $attributes->class([
    'bc-disclosure',
    'bc-disclosure--compact' => $compact,
]) }} @if ($open) open @endif>
    <summary class="bc-disclosure__summary">{{ $title }}</summary>
    <div class="bc-disclosure__body">{{ $slot }}</div>
</details>
