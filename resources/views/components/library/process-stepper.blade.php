@props([
    'steps' => [],
    'label' => 'Fortschritt',
])

<ol {{ $attributes->class('bc-process-stepper') }} aria-label="{{ $label }}">
    @foreach ($steps as $step)
        @php
            $status = in_array($step['status'] ?? '', ['done', 'current', 'upcoming', 'error'], true)
                ? $step['status']
                : 'upcoming';
        @endphp
        <li @class(['bc-process-stepper__step', 'bc-process-stepper__step--'.$status]) @if ($status === 'current') aria-current="step" @endif>
            <span class="bc-process-stepper__node" aria-hidden="true">
                @if ($status === 'done') ✓
                @elseif ($status === 'error') !
                @else {{ $loop->iteration }}
                @endif
            </span>
            <span class="bc-process-stepper__copy">
                <strong>{{ $step['label'] ?? 'Schritt '.$loop->iteration }}</strong>
                @if (filled($step['description'] ?? null))
                    <span>{{ $step['description'] }}</span>
                @endif
            </span>
        </li>
    @endforeach
</ol>
