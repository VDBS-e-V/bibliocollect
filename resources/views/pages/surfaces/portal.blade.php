@php
    $preview = $preview ?? false;
    $patron = $patron ?? null;
    $openLoans = $openLoans ?? collect();
    $openReservations = $openReservations ?? collect();
    $renewalBlocks = $renewalBlocks ?? [];
    $reservationPositions = $reservationPositions ?? [];
@endphp

<x-app-shell surface="portal" title="Mein Konto" :preview="$preview">
    <x-ui.page-header
        kicker="Mein Konto"
        title="Übersicht"
        lead="Eigene Ausleihen, Vormerkungen und Kontodaten auf einen Blick."
    />

    @if (session('portal_success'))
        <x-ui.alert variant="success" title="Erledigt">{{ session('portal_success') }}</x-ui.alert>
    @endif

    @if (session('portal_error'))
        <x-ui.alert variant="error" title="Nicht möglich">{{ session('portal_error') }}</x-ui.alert>
    @endif

    <div class="bc-work-layout">
        <aside class="bc-section-nav" aria-label="Kontobereiche">
            <strong>Mein Konto</strong>
            <a href="#ausleihen">Ausleihen</a>
            <a href="#vormerkungen">Vormerkungen</a>
        </aside>

        <div class="bc-work-layout__main">
            @if (! $preview && $patron === null)
                <x-ui.alert title="Noch nicht verknüpft">
                    Dein Onlinekonto ist noch nicht mit einem Ausleihkonto verknüpft. Frag in der Bibliothek nach einem Verknüpfungscode, dann siehst du hier deine Ausleihen und Vormerkungen.
                </x-ui.alert>
            @endif

            <section class="bc-content-section" id="ausleihen" aria-labelledby="loan-heading">
                <div class="bc-section-heading bc-section-heading--with-meta">
                    <h2 id="loan-heading">Aktuelle Ausleihen</h2>
                    <span>{{ $openLoans->count() }} {{ $openLoans->count() === 1 ? 'Medium' : 'Medien' }}</span>
                </div>
                <x-ui.table>
                    <thead>
                        <tr><th scope="col">Titel</th><th scope="col">Fällig</th><th scope="col">Status</th><th scope="col">Aktion</th></tr>
                    </thead>
                    <tbody>
                        @forelse ($openLoans as $loan)
                            @php
                                $blocks = $renewalBlocks[(string) $loan->getKey()] ?? [];
                                $overdue = $loan->due_on->copy()->startOfDay()->lessThan(now()->startOfDay());
                            @endphp
                            <tr>
                                <th scope="row">{{ $loan->copy->edition->title->preferred_title }}</th>
                                <td class="bc-tabular">{{ $loan->due_on->format('d.m.Y') }}</td>
                                <td>
                                    <x-ui.badge :variant="$overdue ? 'danger' : 'success'">{{ $overdue ? 'Überfällig' : 'Ausgeliehen' }}</x-ui.badge>
                                    @if ($loan->renewal_count > 0)
                                        <small class="bc-public-metadata-source">{{ $loan->renewal_count }}-mal verlängert</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($blocks === [])
                                        <form method="post" action="{{ route('portal.loans.renew', ['loanId' => $loan->getKey()]) }}">
                                            @csrf
                                            <x-ui.button type="submit" variant="secondary">Verlängern</x-ui.button>
                                        </form>
                                    @else
                                        <small class="bc-public-metadata-source">{{ implode(' ', $blocks) }}</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="bc-table__empty">Du hast gerade nichts ausgeliehen.</td></tr>
                        @endforelse
                    </tbody>
                </x-ui.table>
            </section>

            <section class="bc-content-section" id="vormerkungen" aria-labelledby="reservation-heading">
                <div class="bc-section-heading bc-section-heading--with-meta">
                    <h2 id="reservation-heading">Vormerkungen</h2>
                    <span>{{ $openReservations->count() }} {{ $openReservations->count() === 1 ? 'Vormerkung' : 'Vormerkungen' }}</span>
                </div>

                @forelse ($openReservations as $reservation)
                    <article class="bc-loan-card">
                        <div class="bc-loan-card__meta">
                            <strong>{{ $reservation->title->preferred_title }}</strong>
                            <div class="bc-loan-card__facts">
                                @if ($reservation->status->value === 'ready')
                                    <x-ui.badge variant="success">Abholbereit</x-ui.badge>
                                    <span>Abholung bis <span class="bc-tabular">{{ $reservation->pickup_until?->format('d.m.Y') ?? '—' }}</span></span>
                                @else
                                    <x-ui.badge>Wartet</x-ui.badge>
                                    <span>Position <span class="bc-tabular">{{ $reservationPositions[(string) $reservation->getKey()] ?? '—' }}</span> in der Warteschlange</span>
                                @endif
                            </div>
                        </div>
                        <div class="bc-loan-card__actions">
                            <form method="post" action="{{ route('portal.reservations.cancel', ['reservationId' => $reservation->getKey()]) }}">
                                @csrf
                                <x-ui.button type="submit" variant="secondary">Stornieren</x-ui.button>
                            </form>
                        </div>
                    </article>
                @empty
                    <p class="bc-section-copy">Keine offenen Vormerkungen. Ist ein Titel gerade ausgeliehen, kannst du ihn auf der Titelseite im Katalog vormerken.</p>
                @endforelse
            </section>

            <section class="bc-content-section" id="einstellungen" aria-labelledby="settings-heading">
                <div class="bc-section-heading"><h2 id="settings-heading">Einstellungen und Daten</h2></div>
                @if (! $preview && auth()->user())
                    <form method="post" action="{{ route('portal.settings') }}" class="bc-calendar-form">
                        @csrf
                        <label class="bc-public-catalog-filter__check" for="reminders_enabled">
                            <input type="hidden" name="reminders_enabled" value="0">
                            <input id="reminders_enabled" type="checkbox" name="reminders_enabled" value="1" @checked(auth()->user()->reminders_enabled)>
                            <span>
                                <strong>Erinnerungen per E-Mail</strong>
                                <small>Rückgabe bald fällig, überfällig und „vorgemerkter Titel liegt bereit“.</small>
                            </span>
                        </label>
                        <x-ui.button type="submit" variant="secondary">Speichern</x-ui.button>
                    </form>
                    @if ($patron)
                        <p class="bc-section-copy bc-section-copy--spaced"><a href="{{ route('portal.my-data') }}">Meine gespeicherten Daten herunterladen</a> (JSON). Ausleihen und Vormerkungen werden drei Jahre nach Abschluss anonymisiert.</p>
                    @endif
                @endif
            </section>

            <x-ui.alert title="Datenschutz">
                Dieses Portal zeigt nur die eigenen Bibliotheksvorgänge. Eine Lehrerrolle erhält dadurch keinen Zugriff auf Ausleihen von Schüler:innen.
            </x-ui.alert>
        </div>
    </div>
</x-app-shell>
