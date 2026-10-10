# Drittanbieter-Software mit eigener Lizenz

BiblioCollect nutzt Open-Source-Pakete (Composer: `composer.lock`, npm: `package-lock.json`). Pakete mit besonderen Lizenzbedingungen sind hier festgehalten.

## TinyMCE (Texteditor der Informationsseiten)

- Paket: `tinymce` (npm), gebündelt in `public/build` (Dateien `rich-text-editor-*.js` und Verwandte), selbst gehostet, ohne Cloud-Dienst.
- Lizenz: **GNU General Public License Version 2 oder später (GPL-2.0-or-later)** oder die kommerzielle Lizenz von Tiny Technologies, Inc. Genutzt wird die GPL-Variante (`license_key: 'gpl'`, einstellbar mit `TINYMCE_LICENSE_KEY`).
- Quelle und Lizenztext: <https://github.com/tinymce/tinymce>, <https://www.tiny.cloud/legal/>; im Paket `node_modules/tinymce/license.md`.
- Sprachdatei Deutsch: `tinymce-i18n` (MIT).
- Hinweis zur Weitergabe: Wer BiblioCollect einschließlich des gebündelten Editors weitergibt, muss die GPL-Bedingungen für den Editor einhalten (Quelltext des Editors ist öffentlich verfügbar; dieses Repository enthält den Quelltext von BiblioCollect). Die Lizenzangabe des Gesamtprojekts (`composer.json`: `proprietary`) ist eine Entscheidung des VDBS e. V.; Alternative zur GPL ist der Kauf einer TinyMCE-Lizenz.
