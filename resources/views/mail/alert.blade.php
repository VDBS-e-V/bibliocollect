<x-mail::message>
# {{ $alertSubject }}

@foreach ($lines as $line)
{{ $line }}

@endforeach
<x-mail::panel>
Diese Meldung kommt aus der Betriebsüberwachung. Gleiche Meldungen werden höchstens alle {{ (int) config('hosting.alert_throttle_minutes', 30) }} Minuten verschickt. Den aktuellen Stand zeigt die Seite „Systemzustand“ in der Verwaltung.
</x-mail::panel>

<x-mail::button :url="route('administration.system.index')">
Systemzustand öffnen
</x-mail::button>
</x-mail::message>
