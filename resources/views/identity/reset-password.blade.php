<x-app-shell surface="public" title="Neues Passwort">
    <div class="mx-auto max-w-5xl py-8 sm:py-12">
        <section>
            <p class="mb-2 text-sm font-bold uppercase tracking-[0.08em] text-app-text-muted">Mein BiblioCollect</p>
            <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Neues Passwort festlegen</h1>
            <p class="mt-4 max-w-2xl text-lg text-app-text-muted">Mindestens 10 Zeichen mit Buchstaben und Zahlen.</p>

            <form method="POST" action="{{ route('password.update') }}" class="mt-8 max-w-xl space-y-5">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div>
                    <label for="email" class="mb-2 block font-bold">E-Mail-Adresse</label>
                    <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email', $email) }}" class="w-full border border-app-border-strong bg-app-surface px-4 py-3 text-app-text">
                    @error('email')<p class="mt-2 text-sm" role="alert"><strong>Fehler:</strong> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password" class="mb-2 block font-bold">Neues Passwort</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required minlength="10" class="w-full border border-app-border-strong bg-app-surface px-4 py-3 text-app-text">
                    @error('password')<p class="mt-2 text-sm" role="alert"><strong>Fehler:</strong> {{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-2 block font-bold">Passwort wiederholen</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="w-full border border-app-border-strong bg-app-surface px-4 py-3 text-app-text">
                </div>

                <button type="submit" class="bc-button bc-button--primary">Passwort speichern</button>
            </form>
        </section>
    </div>
</x-app-shell>
