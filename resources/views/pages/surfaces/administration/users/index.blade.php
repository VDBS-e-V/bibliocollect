<x-app-shell surface="administration" title="Benutzerkonten">
    <x-ui.page-header
        kicker="Verwaltung"
        title="Benutzerkonten"
        lead="Mitarbeitende, Verwaltung und Schüler-AG einladen, Rollen vergeben und Konten deaktivieren. Konten von Schüler:innen entstehen über die Aktivierung mit Verknüpfungscode."
    />

    <div class="bc-context-actions">
        <a href="{{ route('administration.home') }}">← Zurück zur Verwaltung</a>
        <x-ui.button href="{{ route('administration.users.create') }}">Konto anlegen und einladen</x-ui.button>
    </div>

    @if (session('user_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('user_success') }}</x-ui.alert>
    @endif

    <form method="get" action="{{ route('administration.users.index') }}" class="bc-audit-filter" role="search">
        <x-ui.input label="Name oder E-Mail" name="q" id="user-search" :value="$term" />
        <x-ui.select label="Rolle" name="rolle" id="user-role" data-auto-submit>
            <option value="">Alle Rollen</option>
            @foreach ($roles as $key => $definition)
                <option value="{{ $key }}" @selected($role === $key)>{{ $definition['label'] }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select label="Stand" name="status" id="user-state" data-auto-submit>
            <option value="">Alle</option>
            <option value="aktiv" @selected($state === 'aktiv')>Aktiv</option>
            <option value="eingeladen" @selected($state === 'eingeladen')>Eingeladen, noch nicht angemeldet</option>
            <option value="deaktiviert" @selected($state === 'deaktiviert')>Deaktiviert</option>
        </x-ui.select>
        <x-ui.button type="submit" variant="secondary">Suchen</x-ui.button>
    </form>

    <section class="bc-content-section" aria-labelledby="users-heading">
        <div class="bc-section-heading bc-section-heading--with-meta">
            <h2 id="users-heading">Konten</h2>
            <span>{{ $users->total() }}</span>
        </div>

        @if ($users->isEmpty())
            <p class="bc-section-copy">Keine Konten gefunden.</p>
        @else
            <table class="bc-calendar-table">
                <thead>
                    <tr><th scope="col">Name</th><th scope="col">E-Mail</th><th scope="col">Rollen</th><th scope="col">Stand</th></tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <th scope="row"><a href="{{ route('administration.users.edit', ['userId' => $user->getKey()]) }}">{{ $user->name }}</a></th>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->roleAssignments->map(fn ($assignment) => $roles[$assignment->role_key]['label'] ?? $assignment->role_key)->implode(', ') ?: '—' }}</td>
                            <td>
                                @if (! $user->isEnabled())
                                    <x-ui.badge variant="danger">Deaktiviert</x-ui.badge>
                                @elseif ($user->email_verified_at === null)
                                    <x-ui.badge variant="warning">Eingeladen</x-ui.badge>
                                @else
                                    <x-ui.badge variant="success">Aktiv</x-ui.badge>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{ $users->links() }}
        @endif
    </section>
</x-app-shell>
