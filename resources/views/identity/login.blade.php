<x-app-shell surface="public" title="Anmelden">
    <div class="mx-auto max-w-5xl py-8 sm:py-12">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <section>
                <p class="mb-2 text-sm font-bold uppercase tracking-[0.08em] text-app-text-muted">Mein BiblioCollect</p>
                <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Anmelden</h1>
                <p class="mt-4 max-w-2xl text-lg text-app-text-muted">Melde dich mit deinem bereits aktivierten Onlinekonto an. Ausleihen ohne Onlinekonto bleiben weiterhin möglich.</p>

                <form method="POST" action="{{ route('login.store') }}" class="mt-8 max-w-xl space-y-5">
                    @csrf

                    <div>
                        <label for="email" class="mb-2 block font-bold">E-Mail-Adresse</label>
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" class="w-full border border-app-border-strong bg-app-surface px-4 py-3 text-app-text">
                        @error('email')<p class="mt-2 text-sm" role="alert"><strong>Fehler:</strong> {{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="mb-2 block font-bold">Passwort</label>
                        <input id="password" name="password" type="password" autocomplete="current-password" required class="w-full border border-app-border-strong bg-app-surface px-4 py-3 text-app-text">
                        @error('password')<p class="mt-2 text-sm" role="alert"><strong>Fehler:</strong> {{ $message }}</p>@enderror
                    </div>

                    <label class="flex items-center gap-3">
                        <input name="remember" type="checkbox" value="1">
                        <span>Angemeldet bleiben</span>
                    </label>

                    <button type="submit" class="bc-button bc-button--primary">Anmelden</button>
                </form>
            </section>

            <aside class="border-l-4 border-app-primary bg-app-surface-muted p-6">
                <h2 class="text-xl font-black">Noch kein Onlinekonto?</h2>
                <p class="mt-3 text-app-text-muted">Lass dir in der Bibliothek einen einmaligen Verknüpfungscode geben. Damit verbindest du dein bestehendes Ausleihkonto mit einem Onlinekonto.</p>
                <a class="mt-5 inline-block font-bold" href="{{ route('identity.claim.create') }}">Konto aktivieren</a>
            </aside>
        </div>
    </div>
</x-app-shell>
