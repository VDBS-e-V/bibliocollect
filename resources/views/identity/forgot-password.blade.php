<x-app-shell surface="public" title="Passwort vergessen">
    <div class="mx-auto max-w-5xl py-8 sm:py-12">
        <section>
            <p class="mb-2 text-sm font-bold uppercase tracking-[0.08em] text-app-text-muted">Mein BiblioCollect</p>
            <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Passwort vergessen</h1>
            <p class="mt-4 max-w-2xl text-lg text-app-text-muted">Gib die E-Mail-Adresse deines Onlinekontos ein. Wir schicken dir einen Link, mit dem du ein neues Passwort festlegst.</p>

            @if (session('status'))
                <x-ui.alert variant="success" title="Prüfe dein Postfach">{{ session('status') }}</x-ui.alert>
            @endif

            <form method="POST" action="{{ route('password.email') }}" class="mt-8 max-w-xl space-y-5">
                @csrf

                <div>
                    <label for="email" class="mb-2 block font-bold">E-Mail-Adresse</label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" class="w-full border border-app-border-strong bg-app-surface px-4 py-3 text-app-text">
                    @error('email')<p class="mt-2 text-sm" role="alert"><strong>Fehler:</strong> {{ $message }}</p>@enderror
                </div>

                <button type="submit" class="bc-button bc-button--primary">Link schicken</button>
            </form>

            <p class="mt-6"><a href="{{ route('login') }}">← Zurück zur Anmeldung</a></p>
            <p class="mt-2 text-app-text-muted">Ohne Onlinekonto oder ohne E-Mail-Adresse am Konto hilft die Bibliothek weiter.</p>
        </section>
    </div>
</x-app-shell>
