# Barrierefreiheit (WCAG 2.2 AA)

Ziel: Alle Seiten sind mit Tastatur, Screenreader, Zoom und genug Kontrast nutzbar. Die Prüfung hat drei Stufen.

## 1. Automatisch (bei jedem Pull Request)

| Prüfung | Was sie findet | Wo |
|---|---|---|
| `AccessibilityTest` (Pest) | fehlende Seitensprache, Titel, genau eine Hauptüberschrift, unbeschriftete Felder, Tabellenköpfe, Bilder ohne `alt`, doppelte IDs | `tests/Feature/AccessibilityTest.php`, läuft in „Quality“ |
| axe-core im Browser | Kontrast, Zielgröße (24 px), Namen von Links und Schaltflächen, Landmarken, Links nur durch Farbe erkennbar, ARIA-Fehler | `scripts/a11y/axe-check.mjs`, Workflow „Barrierefreiheit“ |

Der axe-Lauf prüft rund 50 Seiten (öffentlich und mit angemeldeter Verwaltung für Konto, Betrieb und Verwaltung), jeweils **hell und dunkel**, gegen die Regeln WCAG 2.0/2.1/2.2 A und AA sowie die Best Practices. Er scheitert bei Verstößen ab Stufe „serious“. Der Bericht (`a11y-report/report.json`) hängt als Artefakt am Lauf.

Lokal (Anwendung läuft mit Demo-Daten auf Port 8000):

```
npm run a11y
FAIL_ON=minor THEMES=dark BASE_URL=http://127.0.0.1:8010 npm run a11y
```

Umgebungsvariablen stehen im Kopf des Skripts (`BROWSER`, `LOGIN_EMAIL`, `LOGIN_PASSWORD`, `FAIL_ON`, `THEMES`, `REPORT`). Neue Seiten gehören in die Listen `guestPages` oder `memberPages` im Skript.

**Hinweis zum dunklen Design:** Es wird über das Attribut `data-vdbs-theme="dark"` am Wurzelelement geschaltet; das Skript setzt es und wartet die Farbübergänge ab, bevor axe misst.

## 2. Von Hand (vor größeren Releases)

axe findet etwa ein Drittel der Probleme. Diese Punkte prüft ein Mensch; Ergebnisse unten eintragen.

- [ ] **Nur Tastatur:** Tab-Reihenfolge ist logisch, der Fokus ist immer sichtbar, nichts ist nur mit der Maus erreichbar. Besonders: Profilmenü, Dialoge (Bestätigung, Kamera-Scan), Auswahlkarten bei Ausweisen und Etiketten, Tabellen mit Aktionen.
- [ ] **Escape** schließt Menüs und Dialoge, der Fokus kehrt zum Auslöser zurück.
- [ ] **Screenreader (NVDA mit Firefox oder Edge):** Seitentitel und Hauptüberschrift werden angesagt; Formularfelder nennen Beschriftung, Hinweis und Fehler; Statusmeldungen (Treffer gefunden, Fehler beim Scannen) werden vorgelesen; Tabellen haben verständliche Köpfe.
- [ ] **200 % Zoom und 320 px Breite:** kein waagerechtes Scrollen, nichts abgeschnitten (siehe auch Layout-Prüfung für Tablet und Handy).
- [ ] **Windows-Kontrastmodus** (erzwungene Farben): Ränder, Fokus, Schaltflächen und Badges bleiben erkennbar.
- [ ] **Bewegung:** Mit „Animationen reduzieren“ bleibt alles bedienbar.
- [ ] **Kamera-Scan:** Es gibt immer eine Alternative zum Scannen (Eingabe per Tastatur).

### Ergebnisse der Handprüfung

| Datum | Wer | Umfang | Ergebnis |
|---|---|---|---|
| noch offen | | | Die Handprüfung steht aus. Sie braucht eine Person mit NVDA; bis dahin gilt die Erklärung als „teilweise geprüft“. |

## 3. Behoben mit der ersten axe-Prüfung (Issue 32)

- Name von Logo-Link und Profilmenü enthält jetzt den sichtbaren Text (WCAG 2.5.3).
- Kontrast: Linkfarbe im hellen Design dunkler (`--link-default` `#04704f`), Warn-Badges mit eigener Textfarbe (`--text-warning`), im dunklen Design helle Erfolgsfarbe (`--action-success` `#40cd9a`) für Überschriftenzeilen und Badges.
- Links ohne eigene Klasse sind unterstrichen (nicht nur durch Farbe erkennbar) und in Tabellen mindestens 24 px hoch.
- Die zwei Seitennavigationen der Katalogliste (oben, unten) haben unterscheidbare Namen.

## Erklärung zur Barrierefreiheit

Die Erklärung unter „Seiten“ (Verwaltung) wird von der Verwaltung gepflegt. Nach dieser Prüfung sollte sie diese Punkte enthalten:

- Stand: „teilweise vereinbar“ mit WCAG 2.2 AA, automatisch geprüft am Datum des letzten Laufs; Handprüfung mit Screenreader steht aus (bis eingetragen).
- Bekannte Einschränkungen: Der Kamera-Scan braucht eine Kamera; ersatzweise kann die Nummer getippt werden. Etikett- und Ausweisdrucke sind Druckvorlagen und nicht als barrierefreie Dokumente gedacht.
- Kontakt für Rückmeldungen (Adresse der Bibliothek) und Datum der Erstellung.

Der Standardtext der Seite („muss nach einer Prüfung ergänzt werden“) wird nicht per Migration überschrieben, weil die Verwaltung ihn im Echtbetrieb schon angepasst haben kann.
