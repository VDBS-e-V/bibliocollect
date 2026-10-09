# Verwaltung

Für die Verwaltung der Bibliothek.

## Zu Beginn des Schuljahres

1. **Schule → Schuljahre:** Das neue Schuljahr als Entwurf anlegen und die Klassen mit Klassenleitung eintragen.
2. **Schuljahreswechsel:** Die Seite schlägt vor, welche Klasse in welche Klasse wechselt (zum Beispiel 5a → 6a). Prüfe die Zuordnung, wähle bei Abgängen „Ausscheiden“ und bestätige. Danach ist das neue Schuljahr aktiv. Der Wechsel läuft ganz oder gar nicht. Wer noch Medien hat, kann nicht ausscheiden; die Seite nennt die Nummern.
3. Neue Schüler:innen (zum Beispiel die 5. Klasse) importierst du anschließend unter **Ausleihkonten → Klassendaten importieren** (eine Klasse je Datei, die Klassenleitungen füllen die Vorlage aus).

Vor dem Wechsel erstellt die Anwendung **automatisch eine Datensicherung**. Gelingt sie nicht, bleibt alles unverändert. Der Wechsel selbst lässt sich nicht rückgängig machen; im Notfall spielst du die Sicherung zurück (siehe unten).

## Öffnungszeiten und Schließtage

**Öffnungszeiten:** Je Wochentag geöffnet ja/nein und ein oder mehrere Zeiträume (zum Beispiel 08:00–10:00 und 13:00–15:00). **Schließtage:** Ferien und Studientage als Zeitraum eintragen. Fälligkeiten und Abholfristen fallen nie auf einen Schließtag.

## Informationsseiten

**Seiten:** Impressum, Datenschutzerklärung und Erklärung zur Barrierefreiheit bearbeiten. Bis sie ausgefüllt sind, zeigt die Seite den Hinweis „Noch nicht ausgefüllt“.

## Protokoll

**Protokoll** zeigt, wer wann was gebucht oder geändert hat (Ausleihen, Verlängerungen, Katalogänderungen, Kalender). Es enthält Kennungen, keine Namen. Nach drei Jahren wird anonymisiert.

## Datenschutz

- **Auskunft:** Im Ausleihkonto „Auskunft über gespeicherte Daten“ (Ansicht, Druck oder JSON). Personen mit Onlinekonto finden ihre Daten selbst unter „Mein Konto“.
- **Aufbewahrung:** Alles wird drei Jahre nach Abschluss anonymisiert, automatisch jede Woche.
- **Erinnerungs-Mails** lassen sich im Portal abschalten.

## Regeln einstellen

Unter **Verwaltung → Regeln** stellst du Leihfristen, Höchstzahlen, Verlängern, Vormerken, Buchwünsche und Erinnerungen ein. Änderungen gelten sofort für neue Vorgänge; laufende Ausleihen behalten ihr Fälligkeitsdatum. Mit „Alle Regeln zurücksetzen“ gelten wieder die Standardwerte. Jede Änderung steht im Protokoll.

## Benutzerkonten

Unter **Verwaltung → Benutzerkonten** lädst du Mitarbeitende per E-Mail ein (die Person legt das Passwort selbst fest), vergibst Rollen und deaktivierst Konten. Die letzte Verwaltung kann sich nicht selbst aussperren. Ist der Zugang trotzdem verloren, hilft die Einrichtungsseite (`/_setup`, nur mit `SETUP_TOKEN`) mit „Zugang wiederherstellen“.

## Systemzustand und Sicherung

**Verwaltung → Systemzustand** zeigt, ob Cronjob, Warteschlange und Sicherung laufen, und die letzten Fehler. Dort kannst du Zeitplan-Aufgaben **einmal von Hand ausführen**, eine **Sicherung erstellen** und Sicherungen **herunterladen**. Lade sie regelmäßig herunter und lege sie außerhalb des Servers ab. Wiederherstellen: phpMyAdmin → Datenbank wählen → Importieren → die `.sql.gz`-Datei (vorher ist keine Migration nötig). Übe das einmal mit einer leeren Test-Datenbank.

## Update einspielen

Unter **Verwaltung → Update** spielst du eine neue Version ein: Das Paket (ZIP) hochladen oder per FTP in `storage/app/updates` legen, **Jetzt einspielen** wählen. Die Seite ist dabei kurz im Wartungsmodus, vorher wird die Datenbank gesichert, deine Einstellungen (.env), Cover und Ausweis-Motive bleiben unberührt. Mit **Nachts automatisch einspielen** übernimmt der Cron das um 03:15 Uhr. Das Ergebnis steht unter „Letztes Update“.

## Etiketten für Regalbretter

Unter **Regale und Regalbretter → Etiketten für Regalbretter drucken** wählst du, für welche Regalbretter du Etiketten brauchst (alle, ein Regal, ein Bereich oder einzelne Bretter). Das Etikett (105 × 26 mm) zeigt das **Thema** groß, den Standort klein unten rechts und einen Code zum Scannen: Beim Einsortieren scannst du erst das Buch und dann das Etikett am Regalbrett. Als Code wählst du **Strichcode**, **QR-Code** oder keinen. Der QR-Code enthält einen **Link**: Wer ihn mit dem Handy scannt, landet im Katalog und sieht genau die Medien, die auf diesem Regalbrett stehen. Statt des Regalbretts kann der QR-Code auch auf das **Thema** des Bretts zeigen (Auswahl „QR-Code zeigt auf“): Dann sieht man im Katalog alle Regalbretter und Medien des Themas, auch die der Unterthemen. Zum Einsortieren eignet sich das nicht, dort bitte „Dieses Regalbrett“ oder den Strichcode wählen. Das Einsortieren erkennt den Brett-Link, du kannst also mit demselben Etikett arbeiten. Der Link nutzt die Adresse, unter der du die Seite zum Drucken geöffnet hast; drucke die Etiketten also von der echten Adresse (nicht von einer lokalen). Drucke in **tatsächlicher Größe**; sitzt der Druck um einen Millimeter daneben, stellst du ihn oben im Feinabgleich nach.

## Regalbretter, Themenbereiche, Inventarnummern

Die Bibliothek ist so aufgebaut: **Bereichsgruppe › Bereich › Regal › Regalbrett** (zum Beispiel „I. A 1 a“). Unter **Regale und Regalbretter** legst du sie an und gibst ihnen Namen; der Standort eines Exemplars setzt sich daraus zusammen. Themenbereiche und Regalbretter pflegst du unter **Verwaltung**: Jedes Medium bekommt beim Erfassen ein Thema, und unter „Regalbretter“ legst du fest, zu welchen Themenbereichen ein Brett gehört (ein Thema kann auf mehreren Brettern stehen, daraus entsteht der Vorschlag beim Einsortieren); das Einsortieren der Bücher macht die Schüler-AG am Arbeitsplatz („Medien einsortieren“). Alte Inventarnummern stellst du nur unter **Inventarnummern** bewusst auf siebenstellige um; nichts wird automatisch überschrieben.

## Löschverlangen

Verlangt eine Person die Löschung ihrer Daten: Zuerst am Ausleihkonto den **dauerhaften Austritt** buchen (nur ohne offene Ausleihen), dann **Daten jetzt anonymisieren** (nur Verwaltung). Name, Mail, Klasse, Onlinekonto und Ausweis verlieren den Bezug zur Person; das lässt sich nicht rückgängig machen. Ohne Verlangen anonymisiert die Anwendung nach drei Jahren von selbst.
