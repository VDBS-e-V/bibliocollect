<x-app-shell surface="pos" title="Bücher aussondern">
    <x-ui.page-header
        kicker="Katalog und Bestand"
        title="Bücher aussondern"
        lead="Alte, beschädigte oder doppelte Bücher aus dem Bestand nehmen. Erst werden die Inventarnummern geprüft, dann wählst du Grund und Verbleib. Ausgesonderte Bücher bleiben im System und lassen sich zurückholen."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.withdrawal.list') }}">Liste der Aussonderungen (für den Jahresbericht)</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Nicht möglich">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if (session('withdrawal_report'))
        @php($report = session('withdrawal_report'))
        <x-ui.alert title="Was nicht geht">
            @if ($report['blocked'] !== [])<p>Ausgeliehen oder zurückgelegt: {{ implode('; ', $report['blocked']) }}.</p>@endif
            @if ($report['already'] !== [])<p>Schon ausgesondert: {{ implode(', ', $report['already']) }}.</p>@endif
            @if ($report['unknown'] !== [])<p>Unbekannt: {{ implode(', ', $report['unknown']) }}.</p>@endif
        </x-ui.alert>
    @endif

    <section class="bc-work-panel" aria-labelledby="numbers-heading">
        <div class="bc-section-heading"><h2 id="numbers-heading">1. Inventarnummern</h2></div>
        <form method="post" action="{{ route('pos.withdrawal.preview') }}" class="bc-intake-form">
            @csrf
            <div class="bc-field">
                <label class="bc-field__label" for="withdraw-numbers">Inventarnummern der Bücher</label>
                <p class="bc-field__hint">Eine pro Zeile oder durch Leerzeichen getrennt. Mit dem Scanner einfach nacheinander scannen, der Scanner schreibt jede Nummer in eine neue Zeile.</p>
                <textarea id="withdraw-numbers" name="numbers" rows="8" class="bc-field__control" autofocus>{{ old('numbers', $numbers) }}</textarea>
            </div>
            <x-ui.button type="submit">Prüfen</x-ui.button>
        </form>
    </section>
</x-app-shell>
