# T2 – Identity, Patrons und School

T2 führt die ersten fachlichen Kerndaten ein. `User` (Onlinekonto) und `Patron` (Ausleihkonto) bleiben getrennt. Ein Patron kann die Bibliothek vollständig ohne Login nutzen; E-Mail ist am Patron optional, das Geburtsdatum ist für Altersregeln verpflichtend.

## Onlinekonto-Verknüpfung

Die Bibliothek gibt persönlich einen einmaligen Code aus. BiblioCollect speichert nur einen HMAC-Fingerprint, niemals den Klartext-Code. Der Code ist standardmäßig 30 Minuten gültig, wird bei Neuausgabe ersetzt und beim ersten erfolgreichen Verbrauch gesperrt. Danach wird ein neues Onlinekonto mit dem bestehenden Patron verknüpft und die E-Mail-Verifikation ausgelöst.

Die Identity-Domäne kennt das Patrons-Modul nicht konkret. Sie arbeitet gegen `PatronLinkGateway`; die Eloquent-Implementierung liegt im Patrons-Modul. Dadurch bleibt die Abhängigkeit einseitig (`Patrons -> Identity`).

## Rollen und Rechte

Rollen bleiben kombinierbare Bundles aus stabilen Permission-Keys. Zuweisungen werden in `user_role_assignments` persistiert. Fachcode fragt Permissions ab, nicht Rollennamen. Ein technisches Admin-Konto benötigt keinen Patron und erhält nicht automatisch Leserechte auf Ausleihdaten.

## Schule und Öffnungstage

Schuljahre und Klassen sind ULID-basierte Domänenobjekte. `SchoolCalendarService` berechnet reale Bibliotheksöffnungstage aus dem Wochenplan und konkreten Schließtagen. Diese Logik wird später von Click & Collect, Mahnwesen und Ferienregeln wiederverwendet.

## IDs

Neue fachliche Tabellen verwenden ULIDs. Die Laravel-Auth-Tabelle `users` behält vorerst ihren technischen numerischen Primärschlüssel, bekommt aber zusätzlich `public_id` als ULID für modulübergreifende/externe Referenzen. Diese Ausnahme vermeidet eine destruktive Änderung der bereits installierten Laravel-Sessionstruktur in T2.
