<x-mail::message>
# Hallo{{ $wish->patron ? ' '.$wish->patron->first_name : ($wish->contact_name ? ' '.$wish->contact_name : '') }},

@switch($wish->status->value)
@case('accepted')
dein Buchwunsch **„{{ $wish->title }}“** ist angenommen. Wir kümmern uns darum.
@break
@case('ordered')
dein Buchwunsch **„{{ $wish->title }}“** ist bestellt. Sobald das Buch da ist, bekommst du Bescheid.
@break
@case('fulfilled')
gute Nachrichten: **„{{ $wish->title }}“** ist jetzt in der Bibliothek. Du kannst es im Katalog suchen und ausleihen oder vormerken.
@break
@case('declined')
leider können wir deinen Buchwunsch **„{{ $wish->title }}“** nicht erfüllen.
@break
@default
der Stand deines Buchwunsches **„{{ $wish->title }}“** hat sich geändert: {{ $wish->status->label() }}.
@endswitch

@if ($wish->answer)
<x-mail::panel>
**Anmerkung der Bibliothek:** {{ $wish->answer }}
</x-mail::panel>
@endif

@if ($wish->patron)
Deine Wünsche findest du in deinem Konto unter „Buchwünsche“.
@endif

Viele Grüße<br>
Deine Bibliothek
</x-mail::message>
