# BiblioCollect T1 Patch v0.2.0 anwenden

Dieser Patch setzt auf dem GitHub-Stand nach `chore: stabilize frontend CI` auf.
Erwarteter Basis-Commit: `7a82d948fc273ebf79486e94b4d6ab75c8862e99`.

## 1. Vorher prüfen

Im Repository:

```bat
cd C:\xampp\htdocs\bibliocollect
git status
git pull --ff-only
```

Der Working Tree sollte sauber sein.

## 2. Patch prüfen und anwenden

Lege `BiblioCollect_T1_v0.2.0.patch` in das Repository und führe aus:

```bat
git apply --check BiblioCollect_T1_v0.2.0.patch
git apply BiblioCollect_T1_v0.2.0.patch
```

Wenn bereits der Check fehlschlägt, den Patch nicht erzwingen, sondern die Ausgabe weitergeben.

## 3. Qualität prüfen

```bat
php vendor/bin/pint --test
php vendor/bin/phpstan analyse --no-progress --memory-limit=1G
php artisan foundation:check
php vendor/bin/pest
npm run build
```

Es gibt in T1 keine neue Datenbankmigration.

## 4. Lokal ansehen

```bat
php artisan serve
```

Dann:

- `http://127.0.0.1:8000/`
- `http://127.0.0.1:8000/_preview/portal`
- `http://127.0.0.1:8000/_preview/pos`
- `http://127.0.0.1:8000/_preview/administration`

Die `/_preview/*`-Routen werden nur bei `APP_ENV=local` registriert. Die produktiven Routen `/konto`, `/betrieb` und `/verwaltung` sind bereits serverseitig permission-geschützt und liefern ohne autorisiertes Onlinekonto keinen Zugriff.

## 5. Commit

```bat
git add .
git commit -m "feat: add T1 application infrastructure"
git push
```

Danach sollte der GitHub-Workflow `Quality` grün durchlaufen.
