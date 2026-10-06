@php
    $labels = ['checkout' => 'Ausgeliehen', 'renew' => 'Verlängert', 'return' => 'Zurückgegeben'];
    $groups = collect($transaction->items)->groupBy('type');
@endphp
<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #111; line-height: 1.5;">
<p>Hallo{{ $transaction->patron ? ' '.$transaction->patron->first_name : '' }},</p>
<p>hier ist dein Beleg <strong>{{ $transaction->number }}</strong> vom {{ $transaction->created_at?->timezone(config('foundation.business_timezone', 'Europe/Berlin'))->format('d.m.Y, H:i') }} Uhr.</p>

@foreach (['checkout', 'renew', 'return'] as $type)
    @if ($groups->has($type))
        <h3 style="margin: 18px 0 6px;">{{ $labels[$type] }}</h3>
        <table style="border-collapse: collapse; width: 100%;">
            @foreach ($groups[$type] as $item)
                <tr>
                    <td style="border-bottom: 1px solid #ddd; padding: 6px 8px 6px 0;">{{ $item['title'] }}</td>
                    <td style="border-bottom: 1px solid #ddd; padding: 6px 0; white-space: nowrap; text-align: right;">
                        @if ($type === 'return')
                            am {{ \Carbon\Carbon::parse($item['returned_on'])->format('d.m.Y') }}
                        @else
                            fällig am <strong>{{ \Carbon\Carbon::parse($item['due_on'])->format('d.m.Y') }}</strong>
                        @endif
                    </td>
                </tr>
            @endforeach
        </table>
    @endif
@endforeach

<p style="margin-top: 20px;">Bitte gib ausgeliehene Medien bis zum Fälligkeitsdatum zurück. Verlängern kannst du im Portal unter „Mein Konto“, solange niemand den Titel vorgemerkt hat.</p>
<p style="color: #555; font-size: 13px;">{{ config('app.name', 'BiblioCollect') }} · Schulbibliothek</p>
</body>
</html>
