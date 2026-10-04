# T2 v0.3.0 anwenden

## 1. T1 v0.2.2 zuerst sauber festschreiben

Wenn du das zuletzt angepasste Design bereits lokal siehst, aber noch nicht committed hast:

```bat
cd C:\xampp\htdocs\bibliocollect
git status
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan foundation:check
php vendor/bin/pest
npm run build

git add .
git commit -m "style: refine BiblioCollect library interface"
git push
```

Wenn `git status` bereits sauber ist und v0.2.2 committed ist, direkt mit Schritt 2 weiter.

## 2. T2-Patch anwenden

Lege `BiblioCollect_T2_v0.3.0_from_v0.2.2.patch` in das Projektverzeichnis und führe aus:

```bat
cd C:\xampp\htdocs\bibliocollect

git apply --check BiblioCollect_T2_v0.3.0_from_v0.2.2.patch
git apply BiblioCollect_T2_v0.3.0_from_v0.2.2.patch

php artisan optimize:clear
php artisan migrate

php vendor/bin/pint --test
php vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan foundation:check
php vendor/bin/pest
npm run build
```

Es ist **kein `migrate:fresh`** erforderlich. Bestehende User erhalten beim Migrationslauf automatisch eine `public_id`.

## 3. Lokal ansehen

```bat
php artisan serve
```

Neu relevant:

- `/anmelden`
- `/konto-aktivieren`
- `/email-verifizieren` (nach Anmeldung/Registrierung)
- `/konto` ist nun `auth + verified + permission` geschützt
- `/betrieb` und `/verwaltung` ebenfalls

## 4. Commit

```bat
git add .
git commit -m "feat: add T2 identity patrons and school foundation"
git push
```

Danach sollte GitHub Actions `Quality` wieder grün werden.

## Frischer Checkout ohne v0.2.2

Nur falls du auf einem frischen Checkout des derzeit gepushten T1-v0.2.0-Standes startest, zuerst `prerequisite/00_T1_v0.2.2_from_main.patch` anwenden und danach den T2-Patch.
