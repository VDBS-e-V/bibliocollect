<x-app-shell surface="pos" title="Medien einsortieren">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Medien einsortieren"
        lead="Buch scannen, Regalbrett bestätigen, ins Regal stellen. Der Standort wird dabei im System vermerkt. Alles ohne Standort liegt auf dem Stapel."
    />

    @if (session('shelving_notice'))
        <x-ui.alert variant="success" title="Einsortiert">{{ session('shelving_notice') }}</x-ui.alert>
    @endif

    @if (session('shelving_error') || $error)
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('shelving_error') ?? $error }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Fehler">{{ $errors->first() }}</x-ui.alert>
    @endif

    @if (! $copy)
        <section class="bc-work-panel" aria-labelledby="book-scan-heading">
            <div class="bc-section-heading"><h2 id="book-scan-heading">1. Buch scannen</h2></div>
            <form method="get" action="{{ route('pos.shelving') }}" class="bc-pos-scan">
                <x-ui.input
                    label="Inventarnummer des Buchs"
                    name="buch"
                    id="shelving-book"
                    :value="$scanned"
                    hint="Die Nummer vom Etikett scannen oder eintippen. Danach nur noch das Regalbrett bestätigen."
                    autocomplete="off"
                    inputmode="numeric"
                    autofocus
                />
                <x-ui.button type="submit">Weiter</x-ui.button>
            </form>
        </section>
    @else
        <section class="bc-work-panel" aria-labelledby="book-heading">
            <div class="bc-section-heading"><h2 id="book-heading">{{ $copy->edition->title->preferred_title }}</h2></div>
            <p class="bc-section-copy">
                Inventarnummer <strong class="bc-tabular">{{ $copy->barcode }}</strong>
                ·
                @if ($copy->shelf_location)
                    steht bisher auf <strong>{{ $copy->shelf_location }}</strong>
                @else
                    noch kein Standort
                @endif
            </p>

            @if ($shelfOptions === [])
                <p class="bc-section-copy">Es sind noch keine Regalbretter angelegt. Die Verwaltung legt sie unter „Regalbretter“ an.</p>
            @else
                <form method="post" action="{{ route('pos.shelving.scan') }}" class="bc-shelving-form">
                    @csrf
                    <input type="hidden" name="buch" value="{{ $copy->barcode }}">
                    <x-ui.select label="2. Regalbrett" name="regalbrett" id="shelving-shelf">
                        <option value="">Bitte wählen …</option>
                        @foreach ($shelfOptions as $code => $display)
                            <option value="{{ $code }}" @selected($preselected === $code)>{{ $display }}</option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.input label="oder Etikett des Regalbretts scannen" name="regalbrett_code" id="shelving-shelf-code" autocomplete="off" hint="Hat Vorrang vor der Auswahl." />
                    <div class="bc-shelving-form__actions">
                        <x-ui.button type="submit" autofocus>Einsortieren</x-ui.button>
                        <a href="{{ route('pos.shelving') }}">Anderes Buch</a>
                    </div>
                </form>
                @if ($preselected !== '')
                    <p class="bc-section-copy">Vorgewählt ist das Regalbrett der Signatur, sonst das der anderen Exemplare dieser Ausgabe, sonst das zuletzt benutzte.</p>
                @endif
            @endif
        </section>
    @endif

    <section class="bc-content-section" aria-labelledby="stack-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="stack-heading">Stapel „Einsortieren“</h2>
            <span>{{ $stackTotal }}</span>
        </div>

        @if ($stack->isEmpty())
            <p class="bc-section-copy">Der Stapel ist leer. Alles ist einsortiert.</p>
        @else
            <table class="bc-calendar-table">
                <thead>
                    <tr><th scope="col">Inventarnummer</th><th scope="col">Titel</th><th scope="col"><span class="bc-visually-hidden">Einsortieren</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($stack as $item)
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $item->barcode }}</th>
                            <td>{{ $item->edition->title->preferred_title }}</td>
                            <td><a href="{{ route('pos.shelving', ['buch' => $item->barcode]) }}" aria-label="{{ $item->edition->title->preferred_title }} einsortieren">Einsortieren</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            @if ($stackTotal > $stack->count())
                <p class="bc-section-copy">Es werden die ersten {{ $stack->count() }} gezeigt.</p>
            @endif
        @endif
    </section>

    @if ($recent->isNotEmpty())
        <section class="bc-content-section" aria-labelledby="recent-heading">
            <div class="bc-section-heading"><h2 id="recent-heading">Zuletzt einsortiert</h2></div>
            <table class="bc-calendar-table">
                <thead>
                    <tr><th scope="col">Inventarnummer</th><th scope="col">Titel</th><th scope="col">Regalbrett</th></tr>
                </thead>
                <tbody>
                    @foreach ($recent as $item)
                        <tr>
                            <th scope="row" class="bc-tabular">{{ $item->barcode }}</th>
                            <td>{{ $item->edition->title->preferred_title }}</td>
                            <td>{{ $item->shelf_location }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</x-app-shell>
