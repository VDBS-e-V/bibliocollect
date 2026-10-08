@if ($topic->shelves->isNotEmpty())
    Regalbretter: @foreach ($topic->shelves->sortBy('code') as $shelf)<span class="bc-loc-code">{{ $shelf->code }}</span>@endforeach
@else
    <span class="bc-board__none">Noch kein Regalbrett zugeordnet.</span>
@endif
