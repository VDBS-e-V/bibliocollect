<x-app-shell surface="public" title="Konto aktivieren">
    <div class="mx-auto max-w-5xl py-8 sm:py-12">
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem] lg:items-start">
            <section>
                <p class="mb-2 text-sm font-bold uppercase tracking-[0.08em] text-app-text-muted">Mein BiblioCollect</p>
                <h1 class="text-3xl font-black tracking-tight sm:text-4xl">Onlinekonto aktivieren</h1>
                <p class="mt-4 max-w-2xl text-lg text-app-text-muted">Der Code wird persönlich in der Bibliothek ausgegeben und kann nur einmal verwendet werden. Eine E-Mail-Adresse ist nur für das Onlinekonto erforderlich.</p>

                <form method="POST" action="{{ route('identity.claim.store') }}" class="mt-8 max-w-xl space-y-5">
                    @csrf

                    <div>
                        <label for="code" class="mb-2 block font-bold">Verknüpfungscode</label>
                        <input id="code" name="code" type="text" inputmode="text" autocomplete="one-time-code" required value="{{ old('code') }}" placeholder="ABCDE-23456" class="w-full border border-app-border-strong bg-app-surface px-4 py-3 font-mono text-lg uppercase tracking-wider text-app-text">
                        @error('code')<p class="mt-2 text-sm" role="alert"><strong>Fehler:</strong> {{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="email" class="mb-2 block font-bold">E-Mail-Adresse</label>
                        <input id="email" name="email" type="email" autocomplete="email" required value="{{ old('email') }}" class="w-full border border-app-border-strong bg-app-surface px-4 py-3 text-app-text">
                        @error('email')<p class="mt-2 text-sm" role="alert"><strong>Fehler:</strong> {{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password" class="mb-2 block font-bold">Passwort</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" required class="w-full border border-app-border-strong bg-app-surface px-4 py-3 text-app-text">
                        <p class="mt-2 text-sm text-app-text-muted">Mindestens 10 Zeichen sowie Buchstaben und Zahlen.</p>
                        @error('password')<p class="mt-2 text-sm" role="alert"><strong>Fehler:</strong> {{ $message }}</p>@enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-2 block font-bold">Passwort wiederholen</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="w-full border border-app-border-strong bg-app-surface px-4 py-3 text-app-text">
                    </div>

                    <button type="submit" class="bc-button bc-button--primary">Onlinekonto anlegen</button>
                </form>
            </section>

            <aside class="border-l-4 border-app-secondary bg-app-surface-muted p-6">
                <h2 class="text-xl font-black">Wichtig</h2>
                <p class="mt-3 text-app-text-muted">Das Ausleihkonto und das Onlinekonto bleiben getrennte Datensätze. Der Code stellt nur die eindeutige Verknüpfung her.</p>
            </aside>
        </div>
    </div>
</x-app-shell>
