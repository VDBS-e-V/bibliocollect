<x-app-shell surface="public" title="E-Mail bestätigen">
    <div class="mx-auto max-w-3xl py-10 sm:py-16">
        <p class="mb-2 text-sm font-bold uppercase tracking-[0.08em] text-app-text-muted">Mein BiblioCollect</p>
        <h1 class="text-3xl font-black tracking-tight sm:text-4xl">E-Mail-Adresse bestätigen</h1>
        <p class="mt-4 text-lg text-app-text-muted">Wir haben dir einen Bestätigungslink geschickt. Erst nach der Bestätigung ist der Zugang zu deinem Onlinekonto freigeschaltet.</p>

        @if (session('status'))
            <div class="mt-6 border-l-4 border-app-success bg-app-surface-muted p-4" role="status">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('verification.send') }}" class="mt-8">
            @csrf
            <button type="submit" class="bc-button bc-button--secondary">Bestätigungslink erneut senden</button>
        </form>
    </div>
</x-app-shell>
