# Optionaler manueller T2-Test

Der Produktivweg zur Ausgabe eines Verknüpfungscodes bekommt später eine berechtigte POS-Oberfläche. Für die lokale Entwicklung gibt es vorübergehend einen **nur in `APP_ENV=local` erlaubten** Artisan-Hilfsbefehl.

## 1. Test-Patron anlegen

```bat
php artisan tinker
```

Dann in Tinker:

```php
$patron = App\Modules\Patrons\Models\Patron::create([
    'library_number' => 'TEST-1001',
    'kind' => App\Modules\Patrons\Enums\PatronKind::Student,
    'status' => App\Modules\Patrons\Enums\PatronStatus::Active,
    'first_name' => 'Mika',
    'last_name' => 'Test',
    'birth_date' => '2012-05-10',
]);
```

Tinker mit `exit` verlassen.

## 2. Einmalcode ausgeben

```bat
php artisan patron:issue-link-code TEST-1001
```

Der Klartext-Code wird nur einmal angezeigt und nicht gespeichert.

## 3. Onlinekonto aktivieren

Im Browser `/konto-aktivieren` öffnen, Code, E-Mail und Passwort eingeben.

Bei der lokalen Standardkonfiguration `MAIL_MAILER=log` landet die E-Mail-Verifikation in:

```text
storage/logs/laravel.log
```

Nach dem Aufruf des signierten Links ist `/konto` erreichbar.

## 4. Sicherheitsmerkmale prüfen

- derselbe Code kann kein zweites Mal verwendet werden
- eine Neuausgabe widerruft ältere noch offene Codes
- ein Patron benötigt keine E-Mail-Adresse
- das Geburtsdatum ist am Patron verpflichtend
- ein technisches Admin-Konto kann ohne Patron existieren
