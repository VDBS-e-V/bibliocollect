@php
    $labels = ['checkout' => 'Ausgeliehen', 'renew' => 'Verlängert', 'return' => 'Zurückgegeben'];
    $groups = collect($transaction->items)->groupBy('type');
    $notes = collect($transaction->items)->pluck('note')->filter();
@endphp

<x-app-shell surface="pos" title="Beleg {{ $transaction->number }}">
    <div class="bc-class-report-screen">
        <x-ui.page-header kicker="Ausleihe" title="Vorgang abgeschlossen" :lead="'Beleg '.$transaction->number" />

        @if (session('terminal_notice'))
            <x-ui.alert variant="success" title="Erledigt">{{ session('terminal_notice') }}</x-ui.alert>
        @endif

        @foreach ($notes as $note)
            <x-ui.alert title="Bitte beachten">{{ $note }}</x-ui.alert>
        @endforeach

        @if ($errors->any())
            <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
        @endif

        <div class="bc-context-actions">
            <x-ui.button href="{{ route('pos.terminal') }}">Neuer Vorgang</x-ui.button>
            <button type="button" class="bc-button bc-button--secondary" data-print>Beleg drucken</button>
        </div>

        <form method="post" action="{{ route('pos.terminal.receipt.mail', ['transactionId' => $transaction->getKey()]) }}" class="bc-audit-filter">
            @csrf
            <x-ui.input
                label="Beleg per E-Mail senden an"
                name="email"
                type="email"
                :value="old('email', $recipient)"
                :hint="$recipient ? 'Adresse aus dem Ausleihkonto vorbelegt.' : 'Für dieses Ausleihkonto ist keine Adresse hinterlegt.'"
            />
            <x-ui.button type="submit" variant="secondary">Senden</x-ui.button>
        </form>
        @if ($transaction->emailed_at)
            <p class="bc-section-copy">Zuletzt verschickt am {{ $transaction->emailed_at->timezone(config('foundation.business_timezone', 'Europe/Berlin'))->format('d.m.Y H:i') }}.</p>
        @endif
    </div>

    <article class="bc-receipt" aria-label="Beleg {{ $transaction->number }}">
        <h2>Beleg {{ $transaction->number }}</h2>
        <p class="bc-class-report__meta">
            {{ $transaction->created_at?->timezone(config('foundation.business_timezone', 'Europe/Berlin'))->format('d.m.Y, H:i') }} Uhr ·
            {{ config('app.name', 'BiblioCollect') }}
        </p>
        @if ($transaction->patron)
            <p><strong>{{ $transaction->patron->displayName() }}</strong> · {{ $transaction->patron->library_number }}@if ($transaction->patron->schoolClass) · Klasse {{ $transaction->patron->schoolClass->name }}@endif</p>
        @endif

        @foreach (['checkout', 'renew', 'return'] as $type)
            @if ($groups->has($type))
                <h3>{{ $labels[$type] }}</h3>
                <table class="bc-calendar-table">
                    <tbody>
                        @foreach ($groups[$type] as $item)
                            <tr>
                                <th scope="row">{{ $item['title'] }} <small class="bc-public-metadata-source bc-tabular">{{ $item['barcode'] }}</small></th>
                                <td class="bc-tabular">
                                    @if ($type === 'return')
                                        am {{ \Carbon\Carbon::parse($item['returned_on'])->format('d.m.Y') }}
                                    @else
                                        fällig am <strong>{{ \Carbon\Carbon::parse($item['due_on'])->format('d.m.Y') }}</strong>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @endforeach
    </article>
</x-app-shell>
