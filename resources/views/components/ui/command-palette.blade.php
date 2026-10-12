@props([
    'id', 'title' => 'Schnellzugriff', 'label' => 'Aktion oder Bereich suchen',
    'triggerLabel' => 'Schnellzugriff öffnen', 'items' => [],
    'shortcut' => false, 'density' => 'comfortable',
])
@php $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable'; @endphp
<div {{ $attributes->class('bc-command-palette bc-command-palette--'.$density) }} @if ($shortcut) data-ui-command-shortcut @endif>
    <x-ui.button variant="secondary" data-ui-dialog-open="{{ $id }}" :aria-keyshortcuts="$shortcut ? 'Control+k' : null">
        {{ $triggerLabel }}
        @if ($shortcut) <kbd>Strg K</kbd> @endif
    </x-ui.button>
    <x-ui.dialog :id="$id" :title="$title" class="bc-command-palette__dialog" data-ui-command-dialog>
        <label class="bc-command-palette__label" for="{{ $id }}-search">{{ $label }}</label>
        <input type="search" id="{{ $id }}-search" class="bc-command-palette__search"
            data-ui-command-search aria-controls="{{ $id }}-items" autocomplete="off">
        <ul id="{{ $id }}-items" class="bc-command-palette__items" data-ui-command-items>
            @foreach ($items as $item)
                <li data-ui-command-item>
                    @if (filled($item['href'] ?? null) && empty($item['disabled']))
                        <a href="{{ $item['href'] }}">
                            <strong>{{ $item['label'] ?? 'Aktion' }}</strong>
                            @if (filled($item['group'] ?? null)) <span>{{ $item['group'] }}</span> @endif
                        </a>
                    @else
                        <span class="bc-command-palette__disabled" aria-disabled="true">
                            <strong>{{ $item['label'] ?? 'Aktion' }}</strong>
                            @if (filled($item['group'] ?? null)) <span>{{ $item['group'] }}</span> @endif
                        </span>
                    @endif
                </li>
            @endforeach
        </ul>
        <p class="bc-command-palette__empty" data-ui-command-empty hidden>Keine passenden Aktionen vorhanden.</p>
    </x-ui.dialog>
</div>
