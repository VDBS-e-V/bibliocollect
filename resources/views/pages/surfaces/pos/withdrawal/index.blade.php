<x-app-shell surface="pos" title="Bücher aussortieren">
    <x-ui.page-header
        kicker="Katalog und Bestand"
        title="Bücher aussortieren"
        lead="Bücher, die für die Bibliothek uninteressant werden, aus dem Bestand nehmen: Art wählen, Buch scannen, fertig. Das Buch wird sofort als aussortiert vermerkt, bleibt aber im System und lässt sich zurückholen."
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.withdrawal.batch') }}">Mehrere Bücher mit Grund aussondern</a>
        <a href="{{ route('pos.withdrawal.list') }}">Liste der Aussonderungen (für den Jahresbericht)</a>
    </div>

    @if (session('withdrawal_notice'))
        <x-ui.alert variant="success" title="Aussortiert">{{ session('withdrawal_notice') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Nicht möglich">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="post" action="{{ route('pos.withdrawal.scan') }}" class="bc-labels-form">
        @csrf

        <section class="bc-content-section" aria-labelledby="art-heading">
            <div class="bc-section-heading"><h2 id="art-heading">1. Was geschieht mit den Büchern?</h2></div>
            <fieldset class="bc-print-modes">
                <legend class="bc-visually-hidden">Art des Aussortierens</legend>
                @foreach ($fates as $key => $fate)
                    <label class="bc-print-mode">
                        <input type="radio" name="art" value="{{ $key }}" @checked($art === $key) required>
                        <span>
                            <strong>{{ $fate['label'] }}</strong>
                            <small>{{ $fate['text'] }}</small>
                        </span>
                    </label>
                @endforeach
            </fieldset>
        </section>

        <section class="bc-work-panel" aria-labelledby="scan-heading">
            <div class="bc-section-heading"><h2 id="scan-heading">2. Buch scannen</h2></div>
            <div class="bc-pos-scan">
                <x-ui.input
                    label="Inventarnummer des Buchs"
                    name="code"
                    id="withdraw-code"
                    hint="Die Nummer vom Etikett scannen oder eintippen. Der Scanner schickt sie mit der Eingabetaste ab; danach ist das Feld für das nächste Buch frei."
                    autocomplete="off"
                    inputmode="numeric"
                    data-camera-scan="absenden"
                    autofocus
                />
                <x-ui.button type="submit">Aussortieren</x-ui.button>
            </div>
        </section>
    </form>

    <section class="bc-content-section" aria-labelledby="recent-heading">
        <div class="bc-section-heading"><h2 id="recent-heading">Zuletzt aussortiert</h2></div>
        @if ($recent->isEmpty())
            <p class="bc-section-copy">Noch nichts aussortiert.</p>
        @else
            <table class="bc-calendar-table bc-stack-table">
                <thead>
                    <tr><th scope="col">Inventarnummer</th><th scope="col">Titel</th><th scope="col">Art</th><th scope="col"><span class="bc-visually-hidden">Rückgängig</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($recent as $item)
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $item->barcode }}</th>
                            <td data-label="Titel">{{ $item->edition->title->preferred_title }}</td>
                            <td data-label="Art">{{ \App\Modules\Catalog\Enums\WithdrawalFate::describe($item->further_use) }}</td>
                            <td>
                                <form method="post" action="{{ route('pos.withdrawal.restore', ['copyId' => $item->getKey()]) }}">
                                    @csrf
                                    <button type="submit" class="bc-intake-linkbutton" aria-label="{{ $item->edition->title->preferred_title }} zurückholen">Zurückholen</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>
</x-app-shell>
