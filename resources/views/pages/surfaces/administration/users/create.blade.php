<x-app-shell surface="administration" title="Konto anlegen">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Konto anlegen und einladen"
        lead="Die Person bekommt eine E-Mail mit einem Link und legt ihr Passwort selbst fest. Du vergibst nie ein Passwort."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.users.index') }}">← Zurück zu den Benutzerkonten</a>
    </div>

    @if ($errors->any())
        <x-ui.alert variant="error" title="Bitte prüfen">{{ $errors->first() }}</x-ui.alert>
    @endif

    <form method="post" action="{{ route('administration.users.store') }}" class="bc-intake-form">
        @csrf
        <fieldset class="bc-intake-fieldset">
            <legend>Person</legend>
            <x-ui.input label="Name" name="name" id="account-name" :value="old('name')" required maxlength="120" autocomplete="off" />
            <x-ui.input label="E-Mail-Adresse" name="email" id="account-email" type="email" :value="old('email')" required maxlength="190" autocomplete="off" hint="An diese Adresse geht die Einladung. Sie ist zugleich der Anmeldename." />
        </fieldset>

        <fieldset class="bc-intake-fieldset">
            <legend>Rollen</legend>
            <p class="bc-intake-note">Rollen lassen sich kombinieren. Gewählt werden die Rechte, die die Person im Alltag braucht.</p>
            @include('pages.surfaces.administration.users._roles', ['roles' => $roles, 'current' => old('roles', [])])
        </fieldset>

        <div class="bc-intake-actions">
            <x-ui.button type="submit">Konto anlegen und einladen</x-ui.button>
        </div>
    </form>
</x-app-shell>
