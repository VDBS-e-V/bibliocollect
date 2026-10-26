@props(['titleId', 'marked' => false])

@auth
    @can('surface.portal.access')
        <form method="post" action="{{ route('portal.bookmarks.toggle', ['titleId' => $titleId]) }}" class="bc-bookmark">
            @csrf
            <button type="submit" class="bc-bookmark__button" aria-pressed="{{ $marked ? 'true' : 'false' }}">
                <svg viewBox="0 0 24 24" width="1em" height="1em" aria-hidden="true" focusable="false" style="vertical-align:-0.125em"><path d="M12 21s-8-5.2-8-11a4.5 4.5 0 0 1 8-2.8A4.5 4.5 0 0 1 20 10c0 5.8-8 11-8 11z" fill="{{ $marked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/></svg>
                {{ $marked ? 'Gemerkt' : 'Merken' }}
            </button>
        </form>
    @endcan
@else
    <a class="bc-bookmark__login" href="{{ route('login') }}">Zum Merken anmelden</a>
@endauth
