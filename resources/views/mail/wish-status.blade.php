<!DOCTYPE html>
<html lang="de">
<body style="font-family: Arial, Helvetica, sans-serif; color: #111; line-height: 1.5;">
<p>Hallo{{ $wish->patron ? ' '.$wish->patron->first_name : '' }},</p>

@switch($wish->status->value)
    @case('accepted')
        <p>dein Buchwunsch <strong>„{{ $wish->title }}“</strong> ist angenommen. Wir kümmern uns darum.</p>
        @break
    @case('ordered')
        <p>dein Buchwunsch <strong>„{{ $wish->title }}“</strong> ist bestellt. Sobald das Buch da ist, bekommst du Bescheid.</p>
        @break
    @case('fulfilled')
        <p>gute Nachrichten: <strong>„{{ $wish->title }}“</strong> ist jetzt in der Bibliothek. Du kannst es im Katalog suchen und ausleihen oder vormerken.</p>
        @break
    @case('declined')
        <p>leider können wir deinen Buchwunsch <strong>„{{ $wish->title }}“</strong> nicht erfüllen.</p>
        @break
    @default
        <p>der Stand deines Buchwunsches <strong>„{{ $wish->title }}“</strong> hat sich geändert: {{ $wish->status->label() }}.</p>
@endswitch

@if ($wish->answer)
    <p>Anmerkung der Bibliothek: {{ $wish->answer }}</p>
@endif

<p>Deine Wünsche findest du in deinem Konto unter „Buchwünsche“.</p>
<p>Viele Grüße<br>Deine Bibliothek</p>
</body>
</html>
