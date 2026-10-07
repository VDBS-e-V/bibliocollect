@props(['code', 'title'])

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · {{ config('app.name', 'BiblioCollect') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body>
<div class="bc-shell">
    <header class="bc-shell__header">
        <div class="bc-masthead">
            <div class="bc-masthead__inner">
                <a href="{{ url('/') }}" class="bc-brand-lockup" aria-label="BiblioCollect Startseite">
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
    </header>

    <main id="main-content" class="bc-shell__main" tabindex="-1">
        <section class="bc-content-section" aria-labelledby="error-heading">
            <p class="bc-section-copy"><strong>Fehler {{ $code }}</strong></p>
            <h1 id="error-heading">{{ $title }}</h1>
            <div class="bc-section-copy">{{ $slot }}</div>
            <p class="bc-section-copy"><a class="bc-button bc-button--primary" href="{{ url('/') }}">Zur Startseite</a></p>
        </section>
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
