<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #111; line-height: 1.5;">
<p><strong>{{ $alertSubject }}</strong></p>
@foreach ($lines as $line)
    <p style="margin: 4px 0;">{{ $line }}</p>
@endforeach
<p style="margin-top: 18px; color: #555;">Diese Meldung kommt aus der Betriebsüberwachung. Gleiche Meldungen werden höchstens alle {{ (int) config('hosting.alert_throttle_minutes', 30) }} Minuten verschickt. Den aktuellen Stand zeigt die Seite „Systemzustand“ in der Verwaltung.</p>
</body>
</html>
