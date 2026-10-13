{{-- K6b: weitere Bibliotheksbausteine – nur fiktive Vorschau, keine Fachaktionen. --}}
<section class="bc-core-gallery__section" id="bc-core-copy-rows" aria-labelledby="bc-core-copy-rows-heading">
    <x-ui.section-heading eyebrow="23 · Exemplare" title="Eine Exemplarzeile für alle Arbeitsansichten" description="Barcode, Standort und Verfügbarkeit bleiben auch bei langen Titeln und kompakten Listen gut lesbar. Statusfarben dürfen dabei klar hervortreten." />
    <div class="bc-core-gallery__example">
        <div class="bc-core-gallery__example-head">
            <strong id="bc-core-copy-rows-heading">Comfortable / Compact</strong>
            <span>x-library.copy-row</span>
        </div>
        <div class="bc-core-gallery-v6__stack">
            <x-library.copy-row
                title="Zwischen den Zeilen"
                barcode="BC-00003145"
                location="Regal B · 3. Reihe"
                availability="available"
            />
            <x-library.copy-row
                density="compact"
                title="Die außergewöhnlich lange Geschichte von einer Bibliothek, zwei verschwundenen Büchern und unerwarteten Entdeckungen"
                barcode="BC-00003146"
                location="Regal A · 1. Reihe"
                availability="on-loan"
                :dueDays="7"
            />
            <x-library.copy-row
                density="compact"
                title="Was wäre, wenn?"
                barcode="BC-00003147"
                location="Magazin"
                availability="on-loan"
                :dueDays="-2"
            />
        </div>
        <p class="bc-core-gallery-v5__note">Die Beispiel-Exemplare sind fiktiv. Der Baustein zeigt Status und Fälligkeit nur an und enthält keine Ausleihlogik.</p>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-library.copy-row&gt;</code><span>title, barcode, location, availability, dueDays, density und optionaler Aktionsslot.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-patron-choice" aria-labelledby="bc-core-patron-choice-heading">
    <x-ui.section-heading eyebrow="24 · Personen" title="Personenauswahl als zugängliche Auswahlkarte" description="Klare Personendaten, großzügige Auswahlfläche und deutlich markierter Zustand. Die kompakte Variante passt später in Verwaltungslisten." />
    <div class="bc-core-gallery-v6__person-grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head">
                <strong id="bc-core-patron-choice-heading">Eine Person auswählen</strong>
                <span>x-library.patron-choice</span>
            </div>
            <fieldset class="bc-core-gallery-v6__choices">
                <legend>Beispielpersonen (nur lokale Auswahl)</legend>
                <x-library.patron-choice name="bc-demo-patron" value="demo-a" displayName="Mara Beispiel" identifier="Ausweis 0018" detail="Aktives Konto" checked />
                <x-library.patron-choice name="bc-demo-patron" value="demo-b" displayName="Alex Muster" identifier="Ausweis 0072" detail="Bibliothek Süd" />
                <x-library.patron-choice name="bc-demo-patron" value="demo-c" displayName="Nora Beispiel" identifier="Ausweis 0019" detail="Derzeit gesperrt" disabled />
            </fieldset>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head">
                <strong>Kompakte Auswahl</strong>
                <span>density=compact</span>
            </div>
            <fieldset class="bc-core-gallery-v6__choices">
                <legend>Vorführung einer zweiten Gruppe</legend>
                <x-library.patron-choice density="compact" name="bc-demo-patron-compact" value="person-1" displayName="Sam Beispiel" detail="Bibliothek Ost" checked />
                <x-library.patron-choice density="compact" name="bc-demo-patron-compact" value="person-2" displayName="Robin Muster" detail="Bibliothek West" />
            </fieldset>
            <p class="bc-core-gallery-v5__note">Die Auswahl erfolgt über native Radiobuttons und lässt sich mit der Tastatur steuern. Es werden keine Personendaten abgefragt oder gespeichert.</p>
        </div>
    </div>
    <div class="bc-core-gallery__component-note"><code>&lt;x-library.patron-choice&gt;</code><span>name, value, displayName, identifier, detail, checked, disabled, density – keine Profilabfrage und kein Datenzugriff.</span></div>
</section>

<section class="bc-core-gallery__section" id="bc-core-scan-feedback" aria-labelledby="bc-core-scan-feedback-heading">
    <x-ui.section-heading eyebrow="25 · Scanner" title="Farbige Rückmeldungen nach dem Scannen" description="Erfolg, Hinweise und Fehler sind sofort erkennbar – mit klaren VDBS-Farben, aussagekräftigen Texten und ohne durchgehende dekorative Farblinien." />
    <div class="bc-core-gallery-v6__feedback-grid">
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head">
                <strong id="bc-core-scan-feedback-heading">Erfolg und Information</strong>
                <span>x-library.scan-feedback</span>
            </div>
            <div class="bc-core-gallery-v6__stack">
                <x-library.scan-feedback status="success" title="Exemplar erkannt" message="Ein Beispielmedium wurde gefunden." barcode="BC-00003145" />
                <x-library.scan-feedback status="info" title="Scanner bereit" message="Bitte den nächsten Barcode eingeben." compact />
            </div>
        </div>
        <div class="bc-core-gallery__example">
            <div class="bc-core-gallery__example-head">
                <strong>Warnung und Fehler</strong>
                <span>x-library.scan-feedback</span>
            </div>
            <div class="bc-core-gallery-v6__stack">
                <x-library.scan-feedback status="warning" title="Exemplar bereits ausgeliehen" message="Prüfe den angezeigten Medienstatus, bevor du fortfährst." barcode="BC-00003146" />
                <x-library.scan-feedback status="error" title="Barcode nicht gefunden" message="Bitte den Barcode prüfen oder erneut scannen." barcode="BC-00000000" />
            </div>
        </div>
    </div>
    <p class="bc-core-gallery-v5__note">Alle Rückmeldungen sind fiktive UI-Zustände. In späteren Arbeitsabläufen wird der tatsächliche Erfolg erst nach einer Antwort des Fachsystems angezeigt.</p>
    <div class="bc-core-gallery__component-note"><code>&lt;x-library.scan-feedback&gt;</code><span>title, message, barcode, status, compact. Fehler verwenden role=alert, die anderen Zustände role=status.</span></div>
</section>
