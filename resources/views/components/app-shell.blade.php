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
        <div class="bc-shell__header-inner">
            <a href="{{ route('public.home') }}" class="bc-brand-lockup" aria-label="BiblioCollect Startseite">
                <span class="relative block">
                    <img class="bc-brand-lockup__vdbs bc-brand-logo--light" src="/brand/vdbs/logo-light.svg" alt="VDBS">
                    <img class="bc-brand-lockup__vdbs bc-brand-logo--dark" src="/brand/vdbs/logo-dark.svg" alt="VDBS">
                </span>
                <span class="bc-brand-lockup__product">BiblioCollect</span>
            </a>

            <div class="bc-shell__utility">
                <span class="bc-surface-label">{{ $surfaceLabel }}</span>
                <button class="bc-theme-toggle" type="button" data-theme-toggle aria-pressed="false">
                    <span aria-hidden="true">◐</span>
                    <span data-theme-label>Dunkel</span>
                </button>
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
            Entwicklungsansicht: Diese Oberfläche zeigt nur das T1-Grundgerüst; Fachfunktionen sind noch nicht aktiv.
        </div>
    @endif

    <main id="main-content" class="bc-shell__main" tabindex="-1">
        {{ $slot }}
    </main>

    <footer class="bc-shell__footer">
        <div class="bc-shell__footer-inner">
            <span>BiblioCollect</span>
            <span>Eine VDBS-Anwendung</span>
        </div>
    </footer>
</div>
</body>
</html>
