@props([
    'surface' => 'public',
    'title' => null,
    'preview' => false,
])

@php
    use App\Foundation\Navigation\NavigationRegistry;
    use Illuminate\Support\Facades\Gate;

    $navigation = app(NavigationRegistry::class);
    $surfaceLabel = $navigation->surfaceLabel($surface);
    $navigationItems = $preview
        ? $navigation->allForSurface($surface)
        : $navigation->visibleForSurface($surface, static fn (string $permission): bool => Gate::allows($permission));
@endphp

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2DC08E">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name', 'BiblioCollect') }} · VDBS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="bc-skip-link" href="#main-content">Zum Inhalt springen</a>
<div class="bc-shell">
    <header class="bc-shell__header">
        <div class="bc-utility-bar">
            <div class="bc-utility-bar__inner">
                <span>VDBS e. V.</span>
                <div class="bc-utility-bar__actions">
                    <a href="{{ route('public.home') }}">Startseite</a>
                    <span aria-hidden="true">·</span>
                    <span>{{ $surfaceLabel }}</span>
                    <span aria-hidden="true">·</span>
                    @auth
                        @can('surface.portal.access')
                            <a href="{{ route('portal.home') }}">Mein Konto</a>
                            <span aria-hidden="true">·</span>
                        @endcan
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="bc-utility-link">Abmelden</button>
                        </form>
                    @else
                        <a href="{{ route('login') }}">Anmelden</a>
                        <span aria-hidden="true">·</span>
                        <a href="{{ route('identity.claim.create') }}">Konto aktivieren</a>
                        <span aria-hidden="true">·</span>
                    @endauth
                    <button class="bc-theme-toggle" type="button" data-theme-toggle aria-pressed="false">
                        <span data-theme-label>Dunkel</span>
                    </button>
                </div>
            </div>
        </div>

        <div class="bc-masthead">
            <div class="bc-masthead__inner">
                <a href="{{ route('public.home') }}" class="bc-brand-lockup" aria-label="BiblioCollect Startseite">
                    <span class="bc-brand-lockup__logo-wrap">
                        <img class="bc-brand-lockup__vdbs bc-brand-logo--light" src="/brand/vdbs/logo-light.svg" alt="VDBS">
                        <img class="bc-brand-lockup__vdbs bc-brand-logo--dark" src="/brand/vdbs/logo-dark.svg" alt="VDBS">
                    </span>
                    <span class="bc-brand-lockup__text">
                        <strong>BiblioCollect</strong>
                        <small>Schulbibliothek</small>
                    </span>
                </a>
            </div>
        </div>

        @if ($navigationItems !== [])
            <nav class="bc-nav" aria-label="Hauptnavigation">
                <div class="bc-nav__inner">
                    @foreach ($navigationItems as $item)
                        @php
                            $routeName = $preview && $item->previewRoute !== null ? $item->previewRoute : $item->route;
                            $isCurrent = request()->routeIs($item->activePattern) || ($preview && request()->routeIs($item->previewRoute ?? ''));
                        @endphp
                        <a
                            href="{{ route($routeName) }}"
                            @class(['bc-nav__link', 'bc-nav__link--active' => $isCurrent])
                            @if ($isCurrent) aria-current="page" @endif
                        >{{ $item->label }}</a>
                    @endforeach
                </div>
            </nav>
        @endif
    </header>

    @if ($preview)
        <div class="bc-preview-banner" role="status">
            Entwicklungsansicht · Fachfunktionen sind noch nicht aktiv.
        </div>
    @endif

    <main id="main-content" class="bc-shell__main" tabindex="-1">
        {{ $slot }}
    </main>

    <footer class="bc-shell__footer">
        <div class="bc-shell__footer-inner">
            <strong>BiblioCollect</strong>
            <span>Eine Anwendung des VDBS e. V.</span>
        </div>
    </footer>
</div>
</body>
</html>
