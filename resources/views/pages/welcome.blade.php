<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#2DC08E">
    <title>{{ config('app.name', 'BiblioCollect') }} · VDBS</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<div class="bc-shell">
    <header class="bc-shell__header">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 sm:px-8">
            <a href="/" class="bc-brand-lockup no-underline" aria-label="BiblioCollect Startseite">
                <span class="relative block">
                    <img class="bc-brand-lockup__vdbs bc-brand-logo--light" src="/brand/vdbs/logo-light.svg" alt="VDBS">
                    <img class="bc-brand-lockup__vdbs bc-brand-logo--dark" src="/brand/vdbs/logo-dark.svg" alt="VDBS">
                </span>
                <span class="bc-brand-lockup__product">BiblioCollect</span>
            </a>
            <button class="bc-theme-toggle" type="button" data-theme-toggle aria-pressed="false"><span aria-hidden="true">◐</span><span data-theme-label>Dunkel</span></button>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-5 py-8 sm:px-8 sm:py-12">
        <section class="bc-search-stage" aria-labelledby="catalog-heading">
            <div class="bc-search-stage__content">
                <p class="mb-2 text-sm font-bold uppercase tracking-[0.12em] text-app-text-muted">VDBS Bibliothekssoftware</p>
                <h1 id="catalog-heading" class="text-3xl font-black tracking-tight sm:text-4xl">BiblioCollect</h1>
                <p class="mt-3 max-w-2xl text-lg text-app-text-muted">Die modulare Schulbibliotheks-Anwendung. Katalogsuche, Konto und Bibliotheksbetrieb werden schrittweise auf dieser Foundation aufgebaut.</p>
                <div class="bc-search" role="search" aria-label="Katalogsuche Vorschau">
                    <label class="sr-only" for="catalog-search">Titel, Autor:in, ISBN oder Stichwort</label>
                    <input id="catalog-search" class="bc-search__input" type="search" placeholder="Titel, Autor:in, ISBN oder Stichwort …" disabled>
                    <button class="bc-button bc-button--primary" type="button" disabled>Suchen</button>
                </div>
                <p class="mt-2 text-sm text-app-text-muted">Die echte Katalogsuche folgt mit dem Catalog-Modul in T3.</p>
            </div>
        </section>

        <section class="mt-8 grid gap-4 md:grid-cols-3" aria-label="Technischer Projektstand">
            <article class="bc-card p-5"><p class="text-sm font-bold uppercase tracking-wide text-app-text-muted">T0</p><h2 class="mt-2 text-xl font-black">Foundation</h2><p class="mt-2 text-sm text-app-text-muted">Laravel 13, Livewire 4, Vite, Pest, PHPStan, Pint und CI.</p></article>
            <article class="bc-card p-5"><p class="text-sm font-bold uppercase tracking-wide text-app-text-muted">T1</p><h2 class="mt-2 text-xl font-black">VDBS Web UI</h2><p class="mt-2 text-sm text-app-text-muted">Semantische Farbrollen, Light/Dark Theme, Branding und Surface-Grundstruktur.</p></article>
            <article class="bc-card p-5"><p class="text-sm font-bold uppercase tracking-wide text-app-text-muted">Als Nächstes</p><h2 class="mt-2 text-xl font-black">Identity & Patrons</h2><p class="mt-2 text-sm text-app-text-muted">Permissions, Navigation, Onlineidentität, Ausleihkonten und Schulkalender.</p></article>
        </section>
    </main>
</div>
</body>
</html>
