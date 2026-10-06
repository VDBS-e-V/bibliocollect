<x-app-shell surface="pos" title="Vormerkungen">
    <x-ui.page-header
        kicker="Bibliotheksbetrieb"
        title="Vormerkungen"
        lead="Zurückgelegte Exemplare zur Abholung und die Warteschlangen der noch ausgeliehenen Titel."
    />

    <section class="bc-content-section" aria-labelledby="ready-heading">
        <div class="bc-section-heading">
            <h2 id="ready-heading">Zur Abholung zurückgelegt</h2>
            <x-ui.badge>{{ $ready->count() }}</x-ui.badge>
        </div>

        @if ($ready->isEmpty())
            <p class="bc-circulation-note">Aktuell liegt nichts zur Abholung bereit.</p>
        @else
            <div class="bc-loan-list">
                @foreach ($ready as $reservation)
                    <article class="bc-loan-card">
                        <div class="bc-loan-card__meta">
                            <strong>{{ $reservation->title->preferred_title }}</strong>
                            <div class="bc-loan-card__facts">
                                <span>Für:
                                    <a href="{{ route('pos.patrons.show', ['patronId' => $reservation->patron_id]) }}">{{ $reservation->patron->displayName() }}</a>
                                    ({{ $reservation->patron->library_number }})
                                </span>
                                <span>Exemplar: <span class="bc-tabular">{{ $reservation->readyCopy?->barcode ?? '—' }}</span></span>
                                <span>Abholung bis: <span class="bc-tabular">{{ $reservation->pickup_until?->format('d.m.Y') ?? '—' }}</span></span>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="bc-content-section" aria-labelledby="waiting-heading">
        <div class="bc-section-heading">
            <h2 id="waiting-heading">Warteschlangen</h2>
            <x-ui.badge>{{ $waiting->flatten(1)->count() }}</x-ui.badge>
        </div>

        @forelse ($waiting as $titleReservations)
            <article class="bc-loan-card">
                <div class="bc-loan-card__meta">
                    <strong>{{ $titleReservations->first()->title->preferred_title }}</strong>
                    <ol class="bc-loan-card__facts">
                        @foreach ($titleReservations as $reservation)
                            <li>
                                <a href="{{ route('pos.patrons.show', ['patronId' => $reservation->patron_id]) }}">{{ $reservation->patron->displayName() }}</a>
                                <span class="bc-tabular">seit {{ $reservation->requested_at->format('d.m.Y') }}</span>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </article>
        @empty
            <p class="bc-circulation-note">Es wartet niemand auf einen Titel.</p>
        @endforelse
    </section>
</x-app-shell>
