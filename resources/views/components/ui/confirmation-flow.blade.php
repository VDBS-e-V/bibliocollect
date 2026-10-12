@props([
    'id', 'title' => 'Aktion prüfen', 'description' => null, 'acknowledgeLabel' => 'Ich habe die Folgen geprüft.',
    'phrase' => null, 'action' => null, 'submitLabel' => 'Verbindlich bestätigen',
    'density' => 'comfortable',
])
@php $density = in_array($density, ['comfortable', 'compact'], true) ? $density : 'comfortable'; @endphp
<x-ui.dialog :id="$id" :title="$title" :description="$description" tone="warning"
    class="bc-confirmation-flow bc-confirmation-flow--{{ $density }}">
    @if (filled($action))
        <form action="{{ $action }}" method="POST" data-ui-confirm-flow data-ui-confirm-submittable="true">
            @csrf
    @else
        <div data-ui-confirm-flow>
    @endif
        <label class="bc-confirmation-flow__acknowledge" for="{{ $id }}-ack">
            <input id="{{ $id }}-ack" type="checkbox" name="confirmed" value="1" required data-ui-confirm-ack>
            <span>{{ $acknowledgeLabel }}</span>
        </label>
        @if (filled($phrase))
            <label class="bc-confirmation-flow__phrase" for="{{ $id }}-phrase">
                <span>Zur Bestätigung exakt eingeben: <strong>{{ $phrase }}</strong></span>
                <input type="text" id="{{ $id }}-phrase" name="confirmation_phrase" autocomplete="off" required data-ui-confirm-phrase
                    data-ui-confirm-expect="{{ $phrase }}">
            </label>
        @endif
        <p class="bc-confirmation-flow__status" data-ui-confirm-status role="status">Bestätigung noch nicht vollständig.</p>
        <button class="bc-button bc-button--danger" type="{{ filled($action) ? 'submit' : 'button' }}"
            data-ui-confirm-submit disabled>{{ $submitLabel }}</button>
        @if (blank($action)) <p class="bc-confirmation-flow__notice">Nur Vorschau. Ohne Fachaktion kann nichts ausgeführt werden.</p> @endif
    @if (filled($action))
        </form>
    @else
        </div>
    @endif
</x-ui.dialog>
