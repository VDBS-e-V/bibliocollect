<x-app-shell surface="administration" title="E-Mail-Vorschau">
    <x-ui.page-header
        kicker="Verwaltung"
        title="E-Mail-Vorschau"
        lead="So sehen die E-Mails der Anwendung aus. Die Angaben sind erfundene Beispiele; es wird nichts verschickt."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
        <a href="{{ route('administration.system.index') }}">Systemzustand (Testmeldung senden)</a>
    </div>

    @if (session('mail_sent'))
        <x-ui.alert variant="success" title="Verschickt">{{ session('mail_sent') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Nicht möglich">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="post" action="{{ route('administration.mail-preview.send') }}" class="bc-audit-filter">
        @csrf
        <input type="hidden" name="mail" value="{{ $current }}">
        <x-ui.input label="An diese Adresse schicken" name="an" id="mail-to" type="email" :value="old('an', $address)" hint="Zum Beispiel an dich selbst, um die Mail in Gmail oder Outlook zu sehen. Der Betreff beginnt mit „[Vorschau]“." />
        <x-ui.button type="submit" variant="secondary">Diese Mail schicken</x-ui.button>
        <x-ui.button type="submit" variant="secondary" name="alle" value="1" data-confirm="Alle {{ count($catalog) }} Mails an diese Adresse schicken?" data-confirm-label="Alle schicken">Alle {{ count($catalog) }} Mails schicken</x-ui.button>
    </form>

    <div class="bc-mailpreview">
        <nav class="bc-mailpreview__list" aria-label="E-Mails">
            @foreach ($groups as $group => $items)
                <h2 class="bc-mailpreview__group">{{ $group }}</h2>
                <ul>
                    @foreach ($items as $key => $item)
                        <li>
                            <a href="{{ route('administration.mail-preview', array_filter(['mail' => $key, 'breite' => $narrow ? 'handy' : null])) }}" @if ($key === $current) aria-current="page" @endif>{{ $item['label'] }}</a>
                        </li>
                    @endforeach
                </ul>
            @endforeach
        </nav>

        <section class="bc-mailpreview__view" aria-labelledby="mail-subject">
            <div class="bc-mailpreview__bar">
                <p><span class="bc-acc__kind">Betreff</span><strong id="mail-subject">{{ $subject }}</strong></p>
                <p class="bc-mailpreview__width">
                    Breite:
                    <a href="{{ route('administration.mail-preview', ['mail' => $current]) }}" @unless ($narrow) aria-current="true" @endunless>Computer</a>
                    <a href="{{ route('administration.mail-preview', ['mail' => $current, 'breite' => 'handy']) }}" @if ($narrow) aria-current="true" @endif>Handy</a>
                </p>
            </div>
            <iframe class="bc-mailpreview__frame {{ $narrow ? 'bc-mailpreview__frame--narrow' : '' }}" title="Vorschau: {{ $subject }}" srcdoc="{{ $html }}" sandbox></iframe>
        </section>
    </div>
</x-app-shell>
