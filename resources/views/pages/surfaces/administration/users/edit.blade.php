<x-app-shell surface="administration" :title="$account->name">
    <x-ui.page-header
        kicker="Benutzerkonto"
        :title="$account->name"
        :lead="$account->email"
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.users.index') }}">← Zurück zu den Benutzerkonten</a>
        @if ($account->patron_id)
            <a href="{{ route('pos.patrons.show', ['patronId' => $account->patron_id]) }}">Zum Ausleihkonto</a>
        @endif
    </div>

    @if (session('user_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('user_success') }}</x-ui.alert>
    @endif

    @if (session('user_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('user_error') }}</x-ui.alert>
    @endif

    @if ($errors->any())
        <x-ui.alert variant="error" title="Nicht möglich">{{ $errors->first() }}</x-ui.alert>
    @endif

    <section class="bc-content-section" aria-labelledby="state-heading">
        <div class="bc-section-heading"><h2 id="state-heading">Stand</h2></div>
        <p class="bc-section-copy">
            @if (! $account->isEnabled())
                <x-ui.badge variant="danger">Deaktiviert</x-ui.badge> seit {{ $account->disabled_at?->timezone(config('app.timezone'))->format('d.m.Y') }}@if ($account->disabled_reason && $account->disabled_reason !== 'manual'): {{ $account->disabled_reason }}@endif.
            @elseif ($account->email_verified_at === null)
                <x-ui.badge variant="warning">Eingeladen</x-ui.badge> Die Person hat das Passwort noch nicht festgelegt.
            @else
                <x-ui.badge variant="success">Aktiv</x-ui.badge> E-Mail bestätigt am {{ $account->email_verified_at->timezone(config('app.timezone'))->format('d.m.Y') }}.
            @endif
        </p>

        @if ($account->isEnabled())
            <form method="post" action="{{ route('administration.users.invite', ['userId' => $account->getKey()]) }}">
                @csrf
                <x-ui.button type="submit" variant="secondary">{{ $account->email_verified_at === null ? 'Einladung erneut senden' : 'Link zum Zurücksetzen des Passworts senden' }}</x-ui.button>
            </form>
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="roles-heading">
        <div class="bc-section-heading"><h2 id="roles-heading">Rollen</h2></div>
        <form method="post" action="{{ route('administration.users.roles', ['userId' => $account->getKey()]) }}" class="bc-intake-form">
            @csrf
            @method('PUT')
            @include('pages.surfaces.administration.users._roles', ['roles' => $roles, 'current' => old('roles', $current)])
            <div class="bc-intake-actions">
                <x-ui.button type="submit">Rollen speichern</x-ui.button>
            </div>
        </form>
    </section>

    <section class="bc-content-section" aria-labelledby="access-heading">
        <div class="bc-section-heading"><h2 id="access-heading">Zugang</h2></div>
        @if ($account->isEnabled())
            @if (! $account->is(auth()->user()))
                <form method="post" action="{{ route('administration.users.disable', ['userId' => $account->getKey()]) }}" class="bc-audit-filter">
                    @csrf
                    <x-ui.input label="Grund (freiwillig)" name="reason" id="disable-reason" maxlength="200" hint="Zum Beispiel „nicht mehr an der Schule“." />
                    <x-ui.button type="submit" variant="secondary" data-confirm="Das Konto deaktivieren? Die Person wird sofort abgemeldet.">Konto deaktivieren</x-ui.button>
                </form>
            @else
                <p class="bc-section-copy">Das eigene Konto kann nicht deaktiviert werden.</p>
            @endif
        @else
            <form method="post" action="{{ route('administration.users.enable', ['userId' => $account->getKey()]) }}">
                @csrf
                <x-ui.button type="submit">Konto wieder aktivieren</x-ui.button>
            </form>
        @endif
    </section>
</x-app-shell>
