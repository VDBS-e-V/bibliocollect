@php($preview = $preview ?? false)

<x-app-shell surface="pos" title="Bibliotheksbetrieb" :preview="$preview">
    <x-ui.page-header
        kicker="POS"
        title="Bibliotheksbetrieb"
        lead="Der Arbeitsplatz ist auf Barcode-Scanner, Tastatur und schnelle, eindeutige Rückmeldungen ausgelegt."
    />

    <div class="mt-7 grid gap-6 lg:grid-cols-[1.3fr_.7fr]">
        <x-ui.card class="p-5 sm:p-6">
            <h2 class="text-xl font-black">Scanbereich</h2>
            <div class="mt-5">
                <x-ui.input
                    label="Bibliotheksnummer oder Medienbarcode"
                    name="barcode-preview"
                    hint="Scanner arbeiten wie eine Tastatur. In T4 wird das Feld den Ausleihvorgang steuern."
                    placeholder="Barcode scannen …"
                    disabled
                />
            </div>
            <div class="mt-5 flex flex-wrap gap-3">
                <x-ui.button disabled>Ausleihe</x-ui.button>
                <x-ui.button variant="secondary" disabled>Rückgabe</x-ui.button>
            </div>
        </x-ui.card>

        <x-ui.card class="p-5 sm:p-6">
            <h2 class="text-xl font-black">Arbeitsstatus</h2>
            <dl class="mt-4 grid gap-3 text-sm">
                <div class="flex items-center justify-between gap-4"><dt>Scanner</dt><dd><x-ui.badge>bereit für T4</x-ui.badge></dd></div>
                <div class="flex items-center justify-between gap-4"><dt>Ausleihe</dt><dd><x-ui.badge>noch inaktiv</x-ui.badge></dd></div>
                <div class="flex items-center justify-between gap-4"><dt>Rückgabe</dt><dd><x-ui.badge>noch inaktiv</x-ui.badge></dd></div>
            </dl>
        </x-ui.card>
    </div>
</x-app-shell>
