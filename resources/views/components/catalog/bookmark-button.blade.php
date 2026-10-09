@props(['titleId', 'marked' => false])

@auth
    @can('surface.portal.access')
        <form method="post" action="{{ route('portal.bookmarks.toggle', ['titleId' => $titleId]) }}" class="bc-bookmark">
            @csrf
            <button type="submit" class="bc-bookmark__button" aria-pressed="{{ $marked ? 'true' : 'false' }}">
                <span aria-hidden="true">{{ $marked ? '♥' : '♡' }}</span>
                {{ $marked ? 'Gemerkt' : 'Merken' }}
            </button>
        </form>
    @endcan
@else
    <a class="bc-bookmark__login" href="{{ route('login') }}">Zum Merken anmelden</a>
@endauth
