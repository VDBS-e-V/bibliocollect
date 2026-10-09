<form method="post" action="{{ route('administration.topics.feature', ['topicId' => $topic->getKey()]) }}" class="bc-feature-form">
    @csrf
    @if ($topic->featured_position !== null)
        <x-ui.badge variant="success">Empfohlen</x-ui.badge>
        <button type="submit" class="bc-intake-linkbutton">Empfehlung zurücknehmen</button>
    @else
        <button type="submit" class="bc-intake-linkbutton">Auf der Startseite empfehlen</button>
    @endif
</form>
