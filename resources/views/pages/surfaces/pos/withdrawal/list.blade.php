<x-app-shell surface="pos" title="Aussonderungen">
    <x-ui.page-header
        kicker="Katalog und Bestand"
        title="Aussonderungen"
        lead="Welche Bücher in einem Zeitraum ausgesondert wurden, nach Grund und Verbleib. Zum Ausdrucken oder für den Jahresbericht als CSV."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.withdrawal') }}">← Bücher aussondern</a>
        <a href="{{ route('pos.withdrawal.export', ['von' => $from, 'bis' => $to, 'grund' => $reason]) }}">Als CSV herunterladen</a>
        <button type="button" class="bc-intake-linkbutton" data-print>Drucken</button>
    </div>

    @if (session('withdrawal_notice'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('withdrawal_notice') }}</x-ui.alert>
    @endif

    <form method="get" action="{{ route('pos.withdrawal.list') }}" class="bc-audit-filter" role="search">
        <x-ui.input label="Von" name="von" id="withdraw-from" type="date" :value="$from" />
        <x-ui.input label="Bis" name="bis" id="withdraw-to" type="date" :value="$to" />
        <x-ui.select label="Grund" name="grund" id="withdraw-filter-reason" data-auto-submit>
            <option value="">Alle Gründe</option>
            @foreach ($reasons as $case)
                <option value="{{ $case->value }}" @selected($reason === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.button type="submit" variant="secondary">Anzeigen</x-ui.button>
    </form>

    <section class="bc-content-section" aria-labelledby="summary-heading">
        <div class="bc-section-heading bc-section-heading--with-meta"><h2 id="summary-heading">Zusammenfassung</h2><span>{{ $total }}</span></div>
        @if ($byReason === [])
            <p class="bc-section-copy">Im gewählten Zeitraum wurde nichts ausgesondert.</p>
        @else
            <table class="bc-calendar-table">
                <thead><tr><th scope="col">Grund</th><th scope="col">Bücher</th></tr></thead>
                <tbody>
                    @foreach ($byReason as $label => $count)
                        <tr><th scope="row">{{ $label }}</th><td class="bc-tabular">{{ $count }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    @if ($copies->isNotEmpty())
        <section class="bc-content-section" aria-labelledby="list-heading">
            <div class="bc-section-heading"><h2 id="list-heading">Ausgesonderte Bücher</h2></div>
            <table class="bc-calendar-table">
                <thead>
                    <tr><th scope="col">Inventarnummer</th><th scope="col">Titel</th><th scope="col">Am</th><th scope="col">Grund</th><th scope="col">Verbleib</th><th scope="col"><span class="bc-visually-hidden">Zurückholen</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($copies as $copy)
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $copy->barcode }}</th>
                            <td>{{ $copy->edition->title->preferred_title }}</td>
                            <td class="bc-tabular">{{ $copy->depreciated_at?->format('d.m.Y') }}</td>
                            <td>{{ \App\Modules\Catalog\Enums\WithdrawalReason::describe($copy->depreciation_reason) }}</td>
                            <td>{{ \App\Modules\Catalog\Enums\WithdrawalFate::describe($copy->further_use) }}</td>
                            <td>
                                <form method="post" action="{{ route('pos.withdrawal.restore', ['copyId' => $copy->getKey()]) }}">
                                    @csrf
                                    <button type="submit" class="bc-intake-linkbutton" data-confirm="„{{ $copy->edition->title->preferred_title }}“ zurück in den Bestand holen?" aria-label="{{ $copy->barcode }} zurückholen">Zurückholen</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($total > $copies->count())
                <p class="bc-section-copy">Es werden die ersten {{ $copies->count() }} von {{ $total }} gezeigt. Die CSV enthält alle.</p>
            @endif
        </section>
    @endif
</x-app-shell>
