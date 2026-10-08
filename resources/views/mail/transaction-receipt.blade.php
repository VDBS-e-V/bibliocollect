@php
    $labels = ['checkout' => 'Ausgeliehen', 'renew' => 'Verlängert', 'return' => 'Zurückgegeben'];
    $groups = collect($transaction->items)->groupBy('type');
@endphp
<x-mail::message>
# Hallo{{ $transaction->patron ? ' '.$transaction->patron->first_name : '' }},

hier ist dein Beleg **{{ $transaction->number }}** vom {{ $transaction->created_at?->timezone(config('foundation.business_timezone', 'Europe/Berlin'))->format('d.m.Y, H:i') }} Uhr.

@foreach (['checkout', 'renew', 'return'] as $type)
@if ($groups->has($type))
## {{ $labels[$type] }}

<table class="receipt-table" style="border-collapse: collapse; width: 100%; margin: 0 0 18px;">
@foreach ($groups[$type] as $item)
<tr>
<td style="border-bottom: 1px solid #d4dbd8; padding: 8px 8px 8px 0; font-size: 15px;">{{ $item['title'] }}</td>
<td style="border-bottom: 1px solid #d4dbd8; padding: 8px 0; font-size: 15px; white-space: nowrap; text-align: right;">@if ($type === 'return')am {{ \Carbon\Carbon::parse($item['returned_on'])->format('d.m.Y') }}@else fällig am <strong>{{ \Carbon\Carbon::parse($item['due_on'])->format('d.m.Y') }}</strong>@endif</td>
</tr>
@endforeach
</table>

@endif
@endforeach
Bitte gib ausgeliehene Medien bis zum Fälligkeitsdatum zurück. Verlängern kannst du im Portal unter „Mein Konto“, solange niemand den Titel vorgemerkt hat.

Viele Grüße<br>
Deine Bibliothek
</x-mail::message>
