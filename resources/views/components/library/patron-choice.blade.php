@props([
    'name' => 'patron_id',
    'value',
    'displayName',
    'detail' => null,
    'identifier' => null,
    'checked' => false,
    'disabled' => false,
    'density' => 'comfortable',
])

@php
    $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable';
    $id = (string) $attributes->get('id', $name.'-'.md5((string) $value));
    $detailId = $id.'-detail';
@endphp

<label {{ $attributes->except('id')->class([
    'bc-patron-choice',
    'bc-patron-choice--'.$density,
    'bc-patron-choice--disabled' => $disabled,
]) }} for="{{ $id }}">
    <input
        class="bc-patron-choice__input"
        type="radio"
        name="{{ $name }}"
        value="{{ $value }}"
        id="{{ $id }}"
        @checked($checked)
        @disabled($disabled)
        @if (filled($detail) || filled($identifier)) aria-describedby="{{ $detailId }}" @endif
    >
    <span class="bc-patron-choice__avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}</span>
    <span class="bc-patron-choice__copy">
        <strong class="bc-patron-choice__name">{{ $displayName }}</strong>
        @if (filled($detail) || filled($identifier))
            <span class="bc-patron-choice__detail" id="{{ $detailId }}">
                @if (filled($identifier))
                    {{ $identifier }}
                @endif
                @if (filled($identifier) && filled($detail))
                    ·
                @endif
                @if (filled($detail))
                    {{ $detail }}
                @endif
            </span>
        @endif
    </span>
    <span class="bc-patron-choice__check" aria-hidden="true">✓</span>
</label>
