<x-app-shell surface="pos" title="Klassenlisten">
    <div class="bc-class-report-screen">
        <x-ui.page-header
            kicker="Ausleihe"
            title="Klassenlisten"
            lead="Offene Ausleihen je Klasse als Sammelausdruck für die Klassenleitungen. Jede Klasse beginnt auf einer neuen Seite."
        />

        <form method="get" action="{{ route('pos.reports.class-loans') }}" class="bc-audit-filter">
            <x-ui.select label="Welche Ausleihen?" name="modus" data-auto-submit>
                <option value="ueberfaellig" @selected($mode === 'ueberfaellig')>Nur überfällige</option>
                <option value="alle" @selected($mode === 'alle')>Alle offenen</option>
            </x-ui.select>
            <x-ui.select label="Klasse" name="klasse" data-auto-submit>
                <option value="">Alle Klassen</option>
                @foreach ($classes as $class)
                    <option value="{{ $class->getKey() }}" @selected($classId === (string) $class->getKey())>{{ $class->name }}</option>
                @endforeach
            </x-ui.select>
            <button type="button" class="bc-button bc-button--primary" data-print>Drucken</button>
        </form>
    </div>

    @forelse ($groups as $group)
        <section class="bc-class-report" aria-labelledby="class-{{ $loop->index }}">
            <h2 id="class-{{ $loop->index }}">{{ $group['label'] === 'Ohne Klasse' ? 'Ohne Klasse (Lehrkräfte und Mitarbeiter:innen)' : 'Klasse '.$group['label'] }}</h2>
            <p class="bc-class-report__meta">
                {{ $mode === 'ueberfaellig' ? 'Überfällige Ausleihen' : 'Offene Ausleihen' }} · Stand {{ $today->format('d.m.Y') }} · {{ count($group['rows']) }} {{ count($group['rows']) === 1 ? 'Medium' : 'Medien' }}
            </p>
            <table class="bc-calendar-table">
                <thead>
                    <tr>
                        <th scope="col">Name</th>
                        <th scope="col">Medium</th>
                        <th scope="col">Barcode</th>
                        <th scope="col">Fällig</th>
                        <th scope="col">Überfällig</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($group['rows'] as $row)
                        <tr>
                            <th scope="row">{{ $row['patron'] }}</th>
                            <td>{{ $row['title'] }}</td>
                            <td class="bc-tabular">{{ $row['barcode'] }}</td>
                            <td class="bc-tabular">{{ $row['due_on'] }}</td>
                            <td class="bc-tabular">{{ $row['days_overdue'] > 0 ? $row['days_overdue'].' Tage' : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <p class="bc-class-report__note">Bitte erinnern Sie die Schüler:innen an die Rückgabe in der Schulbibliothek.</p>
        </section>
    @empty
        <x-ui.alert title="Nichts zu melden">{{ $mode === 'ueberfaellig' ? 'Es gibt keine überfälligen Ausleihen.' : 'Es gibt keine offenen Ausleihen.' }}</x-ui.alert>
    @endforelse
</x-app-shell>
