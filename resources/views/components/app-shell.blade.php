@props([
    'surface' => 'public',
    'title' => null,
    'preview' => false,
    'letterhead' => null,
])

@php
    use App\Foundation\Navigation\NavigationRegistry;
    use Illuminate\Support\Facades\Gate;

    // Briefpapier nur beim Drucken: Datei je nach Einstellung (farbe, sw) oder aus.
    $letterheadMode = $letterhead ?? config('foundation.letterhead', 'farbe');
    $letterheadFile = ['farbe' => 'briefpapier-farbe.png', 'sw' => 'briefpapier-sw.png'][$letterheadMode] ?? null;

    $navigation = app(NavigationRegistry::class);
    $surfaceLabel = $navigation->surfaceLabel($surface);
    $navigationItems = $preview
        ? $navigation->allForSurface($surface)
        : $navigation->visibleForSurface($surface, static fn (string $permission): bool => Gate::allows($permission));

    // Bereiche, zwischen denen man wechseln kann: nur die, auf die die Person Zugriff hat.
    $areas = collect([
        ['label' => 'Startseite', 'url' => route('public.home'), 'permission' => null, 'current' => request()->routeIs('public.home')],
        ['label' => 'Katalog', 'url' => route('public.catalog.index'), 'permission' => null, 'current' => request()->routeIs('public.catalog.*', 'public.series*', 'public.shelf', 'public.topic')],
                ['label' => 'Bibliotheksbetrieb', 'url' => route('pos.home'), 'permission' => 'surface.pos.access', 'current' => $surface === 'pos'],
        ['label' => 'Verwaltung', 'url' => route('administration.home'), 'permission' => 'surface.administration.access', 'current' => $surface === 'administration'],
    ])->filter(static fn (array $area): bool => $area['permission'] === null || Gate::allows($area['permission']))->values();

    // Das Profilmenü gehört der Person: ihre Ausleihen, Vormerkungen, Buchwünsche und Einstellungen.
    $accountLinks = Gate::allows('surface.portal.access') ? [
        ['label' => 'Mein Konto', 'url' => route('portal.home'), 'current' => request()->routeIs('portal.home')],
        ['label' => 'Meine Ausleihen', 'url' => route('portal.home').'#ausleihen', 'current' => false],
        ['label' => 'Meine Vormerkungen', 'url' => route('portal.home').'#vormerkungen', 'current' => false],
        ['label' => 'Meine Merkliste', 'url' => route('portal.bookmarks'), 'current' => request()->routeIs('portal.bookmarks*')],
        ['label' => 'Meine Leselisten', 'url' => route('portal.reading-lists'), 'current' => request()->routeIs('portal.reading-lists*')],
        ['label' => 'Meine Buchwünsche', 'url' => route('portal.wishes.index'), 'current' => request()->routeIs('portal.wishes.*')],
        ['label' => 'Einstellungen', 'url' => route('portal.home').'#einstellungen', 'current' => false],
        ['label' => 'Meine Daten', 'url' => route('portal.my-data'), 'current' => false],
    ] : [];

    $profile = auth()->user();
    $initials = '';

    if ($profile !== null) {
        $words = preg_split('/\s+/u', trim((string) $profile->name), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $initials = mb_strtoupper(mb_substr($words[0] ?? '?', 0, 1).(count($words) > 1 ? mb_substr($words[count($words) - 1], 0, 1) : ''));
    }
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
<body @class(['bc-has-letterhead' => $letterheadFile !== null])>
<a class="bc-skip-link" href="#main-content">Zum Inhalt springen</a>
@if ($letterheadFile)
    {{-- Wird nur beim Drucken sichtbar und steht auf jeder Seite des Ausdrucks (siehe letterhead.css). --}}
    <img class="bc-letterhead" src="/brand/vdbs/{{ $letterheadFile }}" alt="" aria-hidden="true" width="793" height="1122">
@endif
<div class="bc-shell">
    <header class="bc-shell__header" id="seitenanfang">
        <div class="bc-masthead">
            <div class="bc-masthead__inner">
                <a href="{{ route('public.home') }}" class="bc-brand-lockup" aria-label="BiblioCollect Schulbibliothek, zur Startseite">
                    <span class="bc-brand-lockup__logo-wrap">
                        <img class="bc-brand-lockup__vdbs bc-brand-logo--light" src="/brand/vdbs/logo-light.svg" alt="VDBS">
                        <img class="bc-brand-lockup__vdbs bc-brand-logo--dark" src="/brand/vdbs/logo-dark.svg" alt="VDBS">
                    </span>
                    <span class="bc-brand-lockup__text">
                        <strong>BiblioCollect</strong>
                        <small>Schulbibliothek</small>
                    </span>
                </a>

                <div class="bc-masthead__tools">
                    <span class="bc-masthead__context">{{ $surface === 'public' ? 'Öffentlicher Bereich' : $surfaceLabel }}</span>
                    <button class="bc-theme-toggle bc-theme-toggle--icon" type="button" data-theme-toggle aria-pressed="false" aria-label="Darstellung umschalten">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false"><path d="M20 15.1A8 8 0 0 1 8.9 4a8 8 0 1 0 11.1 11.1Z"/></svg>
                        <span class="sr-only" data-theme-label>Dunkel</span>
                    </button>
                    <div class="bc-account">
                        @auth
                            @can('surface.portal.access')
                                <a class="bc-account__portal-link" href="{{ route('portal.home') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                                    <span>Mein Konto</span>
                                </a>
                            @endcan
                            <details class="bc-account-menu" data-account-menu>
                                <summary class="bc-account-menu__button" aria-label="{{ $initials }}, Profilmenü von {{ $profile->name }}">
                                    <span class="bc-avatar" aria-hidden="true">{{ $initials }}</span>
                                </summary>
                                <div class="bc-account-menu__panel">
                                    <div class="bc-account-menu__head">
                                        <span class="bc-avatar bc-avatar--large" aria-hidden="true">{{ $initials }}</span>
                                        <div>
                                            <strong>{{ $profile->name }}</strong>
                                            <small>{{ $profile->email }}</small>
                                        </div>
                                    </div>
                                    @if ($accountLinks !== [])
                                        <ul class="bc-account-menu__list">
                                            @foreach ($accountLinks as $link)
                                                <li><a href="{{ $link['url'] }}" @if ($link['current']) aria-current="page" @endif>{{ $link['label'] }}</a></li>
                                            @endforeach
                                        </ul>
                                    @endif
                                    <form method="POST" action="{{ route('logout') }}" class="bc-account-menu__logout">
                                        @csrf
                                        <button type="submit">Abmelden</button>
                                    </form>
                                </div>
                            </details>
                        @else
                            <a class="bc-account__portal-link" href="{{ route('login') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                                <span>Mein Konto</span>
                            </a>
                            <a class="bc-account__claim" href="{{ route('identity.claim.create') }}">Konto aktivieren</a>
                        @endauth
                    </div>
                </div>
            </div>
        </div>

        @if ($navigationItems !== [] || $areas->isNotEmpty())
            <nav class="bc-nav" aria-label="Hauptnavigation" data-nav>
                <div class="bc-nav__inner">
                    @foreach ($areas as $area)
                        <a href="{{ $area['url'] }}" @class(['bc-nav__link', 'bc-nav__link--active' => $area['current']]) @if ($area['current']) aria-current="page" @endif data-area-link data-nav-item>{{ $area['label'] }}</a>
                    @endforeach
                    @foreach ($navigationItems as $item)
                        @php
                            $routeName = $preview && $item->previewRoute !== null ? $item->previewRoute : $item->route;
                            $url = route($routeName);
                            $isCurrent = request()->routeIs($item->activePattern) || ($preview && request()->routeIs($item->previewRoute ?? ''));
                        @endphp
                        @continue($areas->contains('url', $url))
                        <a href="{{ $url }}" @class(['bc-nav__link', 'bc-nav__link--active' => $isCurrent]) @if ($isCurrent) aria-current="page" @endif data-nav-item>{{ $item->label }}</a>
                    @endforeach

                    {{-- Für die JavaScript-Navigation müssen dieselben Einträge in derselben Reihenfolge vorhanden sein. --}}
                    <div class="bc-nav__more" data-nav-more hidden>
                        <details class="bc-nav__more-menu" data-account-menu>
                            <summary class="bc-nav__more-button" aria-label="Weitere Menüpunkte">
                                <svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false"><circle cx="5" cy="12" r="2" fill="currentColor"/><circle cx="12" cy="12" r="2" fill="currentColor"/><circle cx="19" cy="12" r="2" fill="currentColor"/></svg>
                            </summary>
                            <ul class="bc-nav__more-list">
                                @foreach ($areas as $area)
                                    <li data-nav-more-item hidden><a href="{{ $area['url'] }}" @if ($area['current']) aria-current="page" @endif>{{ $area['label'] }}</a></li>
                                @endforeach
                                @foreach ($navigationItems as $item)
                                    @php
                                        $moreRoute = $preview && $item->previewRoute !== null ? $item->previewRoute : $item->route;
                                        $moreUrl = route($moreRoute);
                                        $moreCurrent = request()->routeIs($item->activePattern) || ($preview && request()->routeIs($item->previewRoute ?? ''));
                                    @endphp
                                    @continue($areas->contains('url', $moreUrl))
                                    <li data-nav-more-item hidden><a href="{{ $moreUrl }}" @if ($moreCurrent) aria-current="page" @endif>{{ $item->label }}</a></li>
                                @endforeach
                            </ul>
                        </details>
                    </div>
                </div>
            </nav>
        @endif
    </header>

    @if ($preview)
        <div class="bc-preview-banner" role="status">
            Entwicklungsansicht · Fachfunktionen sind noch nicht aktiv.
        </div>
    @endif

    <main id="main-content" @class(['bc-shell__main', 'bc-shell__main--home' => request()->routeIs('public.home')]) tabindex="-1">
        {{ $slot }}
    </main>

    <footer class="bc-shell__footer">
        <div class="bc-shell__footer-inner">
            <div class="bc-footer-grid">
                <div class="bc-footer-brand">
                    <p class="bc-footer-brand__eyebrow">VDBS · Schulbibliothek</p>
                    <strong>BiblioCollect</strong>
                    <p>Ein Ort für Geschichten, Wissen und neue Ideen.</p>
                    <small>Eine Anwendung des VDBS e. V.</small>
                </div>
                <nav class="bc-footer-group" aria-label="Entdecken">
                    <strong>Entdecken</strong>
                    <a href="{{ route('public.catalog.index') }}">Katalog</a>
                    <a href="{{ route('public.series.index') }}">Buchreihen</a>
                    <a href="{{ route('public.wishes.create') }}">Buchwunsch abgeben</a>
                </nav>
                <nav class="bc-footer-group" aria-label="Nutzung">
                    <strong>Bibliothek nutzen</strong>
                    <a href="{{ route('public.home') }}#oeffnungszeiten">Öffnungszeiten</a>
                    @can('surface.portal.access')
                        <a href="{{ route('portal.home') }}">Mein Konto</a>
                    @else
                        <a href="{{ route('login') }}">Anmelden</a>
                    @endcan
                    <a href="{{ route('identity.claim.create') }}">Konto aktivieren</a>
                </nav>
                <nav class="bc-footer-group" aria-label="Rechtliches">
                    <strong>Informationen</strong>
                    <a href="{{ route('public.page', ['slug' => 'impressum']) }}">Impressum</a>
                    <a href="{{ route('public.page', ['slug' => 'datenschutz']) }}">Datenschutz</a>
                    <a href="{{ route('public.page', ['slug' => 'barrierefreiheit']) }}">Barrierefreiheit</a>
                </nav>
            </div>
            <div class="bc-footer-bottom">
                <span>Weil Schule uns alle angeht!</span>
                <a href="#seitenanfang">Zum Seitenanfang ↑</a>
            </div>
        </div>
    </footer>
</div>
</body>
</html>
