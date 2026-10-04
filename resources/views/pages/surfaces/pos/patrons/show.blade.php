@php
    $kindLabel = match ($patron->kind->value) {
        'student' => 'Schüler:in',
        'teacher' => 'Lehrkraft',
        'employee' => 'Mitarbeiter:in',
        default => $patron->kind->value,
    };
    $statusLabel = match ($patron->status->value) {
        'active' => 'Aktiv',
        'departed' => 'Ausgeschieden',
        'archived' => 'Archiviert',
        default => $patron->status->value,
    };
    $roleKeys = $onlineAccount?->roleKeys() ?? [];
@endphp

<x-app-shell surface="pos" :title="$patron->displayName()">
    <x-ui.page-header
        kicker="Ausleihkonto"
        :title="$patron->displayName()"
        :lead="'Bibliotheksnummer '.$patron->library_number"
    />

    <div class="bc-context-actions">
        <a href="{{ route('pos.patrons.index') }}">← Zurück zur Suche</a>
        @can('patrons.manage')
            <x-ui.button href="{{ route('pos.patrons.edit', ['patronId' => $patron->getKey()]) }}" variant="secondary">Stammdaten bearbeiten</x-ui.button>
        @endcan
    </div>

    @if (session('workspace_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('workspace_success') }}</x-ui.alert>
    @endif

    @if (session('workspace_error'))
        <x-ui.alert variant="error" title="Fehler">{{ session('workspace_error') }}</x-ui.alert>
    @endif

    @if ($patron->blocked_at !== null)
        <x-ui.alert variant="error" title="Ausleihkonto gesperrt">
            Für dieses Ausleihkonto ist eine Sperre hinterlegt.
            @can('patrons.sensitive.view')
                @if ($patron->blocked_reason)
                    <span>Grund: {{ $patron->blocked_reason }}</span>
                @endif
            @endcan
        </x-ui.alert>
    @endif

    <div class="bc-patron-detail-layout">
        <div class="bc-patron-detail-layout__main">
            <section class="bc-content-section" aria-labelledby="basis-heading">
                <div class="bc-section-heading"><h2 id="basis-heading">Basisdaten</h2></div>
                <dl class="bc-detail-list">
                    <div><dt>Bibliotheksnummer</dt><dd class="bc-tabular">{{ $patron->library_number }}</dd></div>
                    <div><dt>Typ</dt><dd>{{ $kindLabel }}</dd></div>
                    <div><dt>Klasse</dt><dd>{{ $patron->schoolClass?->name ?? '—' }}</dd></div>
                    <div><dt>Status</dt><dd><x-ui.badge :variant="$patron->status->value === 'active' ? 'success' : 'neutral'">{{ $statusLabel }}</x-ui.badge></dd></div>
                    <div><dt>Ausleihe</dt><dd>{{ $patron->blocked_at === null ? 'nicht gesperrt' : 'gesperrt' }}</dd></div>
                </dl>
            </section>

            @can('patrons.sensitive.view')
                <section class="bc-content-section" aria-labelledby="sensitive-heading">
                    <div class="bc-section-heading">
                        <h2 id="sensitive-heading">Persönliche Daten</h2>
                    </div>
                    <p class="bc-privacy-note">Nur für Mitarbeiter:innen und Verwaltung sichtbar.</p>
                    <dl class="bc-detail-list">
                        <div><dt>Geburtsdatum</dt><dd class="bc-tabular">{{ $patron->birth_date?->format('d.m.Y') ?? '—' }}</dd></div>
                        <div><dt>E-Mail am Ausleihkonto</dt><dd>{{ $patron->email ?: '—' }}</dd></div>
                        <div><dt>Austritt</dt><dd class="bc-tabular">{{ $patron->leaving_on?->format('d.m.Y') ?? '—' }}</dd></div>
                        <div><dt>Sperrgrund</dt><dd>{{ $patron->blocked_reason ?: '—' }}</dd></div>
                    </dl>
                </section>
            @endcan
        </div>

        <aside class="bc-patron-detail-layout__aside">
            <section class="bc-side-panel" aria-labelledby="online-account-heading">
                <h2 id="online-account-heading">Onlinekonto</h2>

                @if ($hasOnlineAccount)
                    <p><x-ui.badge variant="success">Verknüpft</x-ui.badge></p>
                    @can('patrons.sensitive.view')
                        <dl class="bc-side-definition-list">
                            <div><dt>E-Mail</dt><dd>{{ $onlineAccount->email }}</dd></div>
                            <div><dt>Bestätigt</dt><dd>{{ $onlineAccount->email_verified_at ? 'Ja' : 'Nein' }}</dd></div>
                        </dl>
                    @endcan
                @else
                    <p>Noch kein Onlinekonto verknüpft.</p>

                    @can('patrons.link-code.issue')
                        @if ($patron->canLinkOnlineAccount())
                            <form method="post" action="{{ route('pos.patrons.link-code.issue', ['patronId' => $patron->getKey()]) }}" class="bc-inline-form">
                                @csrf
                                <x-ui.button type="submit">Einmalcode ausgeben</x-ui.button>
                            </form>
                            <p>Ein neuer Code widerruft automatisch einen vorherigen, noch offenen Code.</p>
                        @else
                            <p>Für diesen Kontotyp oder Status kann kein Onlinekonto-Code ausgegeben werden.</p>
                        @endif
                    @endcan
                @endif
            </section>

            @if ($onlineAccount)
                @can('identity.roles.assign')
                    <section class="bc-side-panel bc-side-panel--quiet" aria-labelledby="roles-heading">
                        <h2 id="roles-heading">Rollen</h2>

                        <div class="bc-role-summary">
                            @foreach ($roleKeys as $roleKey)
                                <x-ui.badge>{{ config("authorization.roles.{$roleKey}.label", $roleKey) }}</x-ui.badge>
                            @endforeach
                        </div>

                        <p>Hier werden ausschließlich Schüler-AG-Rollen verwaltet. Grund- und Verwaltungsrollen bleiben getrennt.</p>

                        <div class="bc-role-list">
                            @foreach ($studentAgRoles as $roleKey => $definition)
                                @php($assigned = in_array($roleKey, $roleKeys, true))
                                <div class="bc-role-row">
                                    <div>
                                        <strong>{{ $definition['label'] }}</strong>
                                        <span>{{ $assigned ? 'zugewiesen' : 'nicht zugewiesen' }}</span>
                                    </div>
                                    @if ($assigned)
                                        <form method="post" action="{{ route('pos.patrons.ag-roles.destroy', ['patronId' => $patron->getKey(), 'roleKey' => $roleKey]) }}">
                                            @csrf
                                            @method('DELETE')
                                            <x-ui.button type="submit" variant="secondary">Entfernen</x-ui.button>
                                        </form>
                                    @else
                                        <form method="post" action="{{ route('pos.patrons.ag-roles.store', ['patronId' => $patron->getKey(), 'roleKey' => $roleKey]) }}">
                                            @csrf
                                            @method('PUT')
                                            <x-ui.button type="submit" variant="secondary">Zuweisen</x-ui.button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endcan
            @endif

            @can('patrons.manage')
                <section class="bc-side-panel bc-side-panel--quiet" aria-labelledby="management-heading">
                    <h2 id="management-heading">Stammdaten</h2>
                    <p>Name, Bibliotheksnummer, Geburtsdatum, E-Mail, Klasse und geplantes Austrittsdatum können hier gepflegt werden. Kontotyp und Status bleiben eigenen Fachworkflows vorbehalten.</p>
                    <p><x-ui.button href="{{ route('pos.patrons.edit', ['patronId' => $patron->getKey()]) }}" variant="secondary">Bearbeiten</x-ui.button></p>
                </section>
            @endcan

            @can('patrons.block')
                <section class="bc-side-panel bc-side-panel--quiet" aria-labelledby="blocking-heading">
                    <h2 id="blocking-heading">Ausleihsperre</h2>
                    <div class="bc-block-workflow">
                        @if ($patron->blocked_at !== null)
                            <p>Die Ausleihe ist derzeit gesperrt. Das Entsperren wird mit handelnder Person und bisherigem Sperrgrund protokolliert.</p>
                            <form method="post" action="{{ route('pos.patrons.block.destroy', ['patronId' => $patron->getKey()]) }}">
                                @csrf
                                @method('DELETE')
                                <x-ui.button type="submit" variant="secondary">Sperre aufheben</x-ui.button>
                            </form>
                        @elseif ($patron->status->value === 'active')
                            <form method="post" action="{{ route('pos.patrons.block.store', ['patronId' => $patron->getKey()]) }}">
                                @csrf
                                <div class="bc-field">
                                    <label class="bc-field__label" for="block-reason">Sperrgrund</label>
                                    <p class="bc-field__hint" id="block-reason-hint">Der Grund ist nur für Mitarbeiter:innen und Verwaltung sichtbar und wird protokolliert.</p>
                                    <textarea id="block-reason" name="reason" class="bc-field__control bc-field__textarea" maxlength="500" required aria-describedby="block-reason-hint"></textarea>
                                </div>
                                <x-ui.button type="submit">Ausleihkonto sperren</x-ui.button>
                            </form>
                        @else
                            <p>Ausgeschiedene oder archivierte Konten werden nicht zusätzlich gesperrt.</p>
                        @endif
                        <p class="bc-management-note">Die Sperre ist als fachlicher Kontozustand hinterlegt. T4 bezieht sie in die Ausleihentscheidung ein.</p>
                    </div>
                </section>
            @endcan
        </aside>
    </div>
</x-app-shell>
