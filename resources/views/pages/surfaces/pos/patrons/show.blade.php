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
        @can('patrons.sensitive.view')
            <a href="{{ route('pos.patrons.data-export', ['patronId' => $patron->getKey()]) }}">Auskunft über gespeicherte Daten</a>
        @endcan
    </div>

    @if (session('workspace_success'))
        <x-ui.alert variant="success" title="Gespeichert">{{ session('workspace_success') }}</x-ui.alert>
    @endif

    @if (session('workspace_error'))
        <x-ui.alert variant="error" title="Fehler">{{ session('workspace_error') }}</x-ui.alert>
    @endif

    @if ($patron->blocked_at !== null && $patron->isActive())
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

            @can('circulation.manage')
                <section class="bc-content-section bc-circulation-workspace" aria-labelledby="circulation-heading">
                    <div class="bc-section-heading">
                        <h2 id="circulation-heading">Ausleihe und Rückgabe</h2>
                        <x-ui.badge>{{ $openLoans->count() }} offen</x-ui.badge>
                    </div>

                    @if ($patron->isActive() && $patron->blocked_at === null)
                        <form method="post" action="{{ route('pos.circulation.checkout', ['patronId' => $patron->getKey()]) }}" class="bc-circulation-checkout">
                            @csrf
                            <x-ui.input
                                label="Exemplar-Barcode"
                                name="barcode"
                                :value="old('barcode')"
                                :error="$errors->first('barcode') ?: null"
                                autocomplete="off"
                                autofocus
                            />
                            <x-ui.button type="submit">Ausleihen</x-ui.button>
                        </form>
                    @else
                        <p class="bc-circulation-note">Für dieses Ausleihkonto können aktuell keine neuen Exemplare ausgeliehen werden. Bereits offene Ausleihen können weiterhin zurückgegeben werden.</p>
                    @endif

                    <div class="bc-loan-list" aria-label="Offene Ausleihen">
                        @forelse ($openLoans as $loan)
                            @php
                                $copy = $loan->copy;
                                $edition = $copy->edition;
                                $title = $edition->title;
                            @endphp
                            <article class="bc-loan-card">
                                <div class="bc-loan-card__meta">
                                    <strong>{{ $title->preferred_title }}</strong>
                                    <div class="bc-loan-card__facts">
                                        <span>Barcode: <span class="bc-tabular">{{ $copy->barcode }}</span></span>
                                        <span>Fällig: <span class="bc-tabular">{{ $loan->due_on->format('d.m.Y') }}</span></span>
                                        @if ($edition->edition_statement)
                                            <span>{{ $edition->edition_statement }}</span>
                                        @endif
                                        @if ($loan->renewal_count > 0)
                                            <span>{{ $loan->renewal_count }}-mal verlängert</span>
                                        @endif
                                    </div>
                                    @php($blocks = $renewalBlocks[(string) $loan->getKey()] ?? [])
                                    @if ($blocks !== [])
                                        <p class="bc-circulation-note">Keine Verlängerung: {{ implode(' ', $blocks) }}</p>
                                    @endif
                                </div>
                                <div class="bc-loan-card__actions">
                                    @if (($renewalBlocks[(string) $loan->getKey()] ?? []) === [])
                                        <form method="post" action="{{ route('pos.circulation.renew', ['patronId' => $patron->getKey(), 'loanId' => $loan->getKey()]) }}">
                                            @csrf
                                            <x-ui.button type="submit" variant="secondary">Verlängern</x-ui.button>
                                        </form>
                                    @endif
                                    <form method="post" action="{{ route('pos.circulation.return', ['patronId' => $patron->getKey(), 'loanId' => $loan->getKey()]) }}">
                                        @csrf
                                        <x-ui.button type="submit" variant="secondary">Zurückgeben</x-ui.button>
                                    </form>
                                </div>
                            </article>
                        @empty
                            <p class="bc-circulation-note">Derzeit sind keine Exemplare auf dieses Ausleihkonto ausgeliehen.</p>
                        @endforelse
                    </div>

                    <p class="bc-circulation-note">Angezeigt werden nur laufende Ausleihen. Bereits zurückgegebene Titel werden in diesem Arbeitsbereich nicht als Lesehistorie aufgeführt.</p>

                    <div class="bc-section-heading">
                        <h3 id="reservations-heading">Vormerkungen</h3>
                        <x-ui.badge>{{ $openReservations->count() }} offen</x-ui.badge>
                    </div>

                    @if ($errors->has('reservation'))
                        <x-ui.alert variant="error" title="Vormerkung nicht möglich">{{ $errors->first('reservation') }}</x-ui.alert>
                    @endif

                    @if ($patron->isActive() && $patron->blocked_at === null)
                        <form method="post" action="{{ route('pos.reservations.store', ['patronId' => $patron->getKey()]) }}" class="bc-circulation-checkout">
                            @csrf
                            <x-ui.input
                                label="Titel vormerken (Exemplar-Barcode oder ISBN)"
                                name="identifier"
                                :value="old('identifier')"
                                autocomplete="off"
                            />
                            <x-ui.button type="submit" variant="secondary">Vormerken</x-ui.button>
                        </form>
                    @endif

                    <div class="bc-loan-list" aria-label="Offene Vormerkungen">
                        @forelse ($openReservations as $reservation)
                            <article class="bc-loan-card">
                                <div class="bc-loan-card__meta">
                                    <strong>{{ $reservation->title->preferred_title }}</strong>
                                    <div class="bc-loan-card__facts">
                                        @if ($reservation->status->value === 'ready')
                                            <x-ui.badge variant="success">Abholbereit</x-ui.badge>
                                            <span>Exemplar: <span class="bc-tabular">{{ $reservation->readyCopy?->barcode ?? '—' }}</span></span>
                                            <span>Abholung bis: <span class="bc-tabular">{{ $reservation->pickup_until?->format('d.m.Y') ?? '—' }}</span></span>
                                        @else
                                            <x-ui.badge>Wartet</x-ui.badge>
                                            <span>Position <span class="bc-tabular">{{ $reservationPositions[(string) $reservation->getKey()] ?? '—' }}</span> in der Warteschlange</span>
                                        @endif
                                        <span>Vorgemerkt am <span class="bc-tabular">{{ $reservation->requested_at->format('d.m.Y') }}</span></span>
                                    </div>
                                </div>
                                <div class="bc-loan-card__actions">
                                    <form method="post" action="{{ route('pos.reservations.cancel', ['patronId' => $patron->getKey(), 'reservationId' => $reservation->getKey()]) }}">
                                        @csrf
                                        <x-ui.button type="submit" variant="secondary">Stornieren</x-ui.button>
                                    </form>
                                </div>
                            </article>
                        @empty
                            <p class="bc-circulation-note">Keine offenen Vormerkungen. Vorgemerkt werden kann ein Titel, solange alle Exemplare ausgeliehen sind.</p>
                        @endforelse
                    </div>
                </section>
            @endcan
        </div>

        <aside class="bc-patron-detail-layout__aside">
            <section class="bc-side-panel" aria-labelledby="online-account-heading">
                <h2 id="online-account-heading">Onlinekonto</h2>

                @if ($hasOnlineAccount)
                    @if ($onlineAccount !== null)
                        <p><x-ui.badge :variant="$onlineAccount->isEnabled() ? 'success' : 'neutral'">{{ $onlineAccount->isEnabled() ? 'Verknüpft' : 'Deaktiviert' }}</x-ui.badge></p>
                    @else
                        <p><x-ui.badge variant="success">Verknüpft</x-ui.badge></p>
                    @endif
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

            @if ($onlineAccount && $patron->isActive())
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

            @can('patrons.depart')
                <section class="bc-side-panel bc-side-panel--quiet" aria-labelledby="departure-heading">
                    <h2 id="departure-heading">Dauerhafter Austritt</h2>
                    @if ($patron->isActive())
                        <div class="bc-departure-workflow">
                            <p>Der Austritt setzt den Kontostatus auf „Ausgeschieden“, entfernt die aktuelle Klassenzuordnung, widerruft offene Aktivierungscodes und deaktiviert ein verknüpftes Onlinekonto.</p>
                            <form method="post" action="{{ route('pos.patrons.departure.store', ['patronId' => $patron->getKey()]) }}" class="bc-departure-workflow">
                                @csrf
                                <x-ui.input
                                    label="Austrittsdatum"
                                    name="leaving_on"
                                    type="date"
                                    :value="old('leaving_on', app(\App\Foundation\Support\BusinessClock::class)->now()->toDateString())"
                                    :error="$errors->first('leaving_on') ?: null"
                                />
                                <label class="bc-departure-confirm">
                                    <input type="checkbox" name="confirm_departure" value="1" required>
                                    <span>Ich bestätige, dass die Person dauerhaft ausgeschieden ist. Dieser Statuswechsel wird protokolliert.</span>
                                </label>
                                @error('confirm_departure')
                                    <p class="bc-field__error"><strong>Fehler:</strong> {{ $message }}</p>
                                @enderror
                                <x-ui.button type="submit">Als ausgeschieden markieren</x-ui.button>
                            </form>
                            <p class="bc-management-note">Reservierungen und weitere Fachfälle reagieren später über das Ereignis <code>PatronDeparted</code>, sobald die betreffenden Module implementiert sind.</p>
                        </div>
                    @else
                        <p>Dieses Ausleihkonto ist nicht mehr aktiv. Ein weiterer Austritt ist nicht möglich.</p>
                    @endif
                </section>
            @endcan

            @can('patrons.block')
                <section class="bc-side-panel bc-side-panel--quiet" aria-labelledby="blocking-heading">
                    <h2 id="blocking-heading">Ausleihsperre</h2>
                    <div class="bc-block-workflow">
                        @if ($patron->blocked_at !== null && $patron->isActive())
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
