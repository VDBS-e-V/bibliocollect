<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$targetDirectory = $root.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'vdbs';
$legacyDirectory = $root.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR.'fonts'.DIRECTORY_SEPARATOR.'vdbs';
$cleanup = in_array('--cleanup', $argv, true);
$checkOnly = in_array('--check-only', $argv, true);

$fonts = [
    [
        'target' => 'Lato-Regular.ttf',
        'sha256' => 'e82542aed8293f49fc83c4aaea566b1f6b4fc7a9ab5da11e6fb9bc0973b5324b',
        'archive' => 'Lato,Source_Serif_4.zip',
        'entry' => 'Lato/Lato-Regular.ttf',
    ],
    [
        'target' => 'Lato-Italic.ttf',
        'sha256' => '3be26bf6973f49df6a7dfd130041017354342bfbb023e6b9610b42daeba6de34',
        'archive' => 'Lato,Source_Serif_4.zip',
        'entry' => 'Lato/Lato-Italic.ttf',
    ],
    [
        'target' => 'Lato-Bold.ttf',
        'sha256' => 'd7f0b7f2570f2f28b504da1181b4d71b1420b10be2c4fd690927f1c8ee3b19c3',
        'archive' => 'Lato,Source_Serif_4.zip',
        'entry' => 'Lato/Lato-Bold.ttf',
    ],
    [
        'target' => 'Lato-Black.ttf',
        'sha256' => 'abf64cfa14645043a7c33f76435125f8b3de79c510adb938a1c16085518d4341',
        'archive' => 'Lato,Source_Serif_4.zip',
        'entry' => 'Lato/Lato-Black.ttf',
    ],
    [
        'target' => 'SourceSerif4-Regular.ttf',
        'sha256' => 'c8f9ad25c4ccb22953933af7cd50eb0cdaa7d09982f6c999a3727cb27592a6d0',
        'archive' => 'Lato,Source_Serif_4.zip',
        'entry' => 'Source_Serif_4/static/SourceSerif4-Regular.ttf',
    ],
    [
        'target' => 'SourceSerif4-Italic.ttf',
        'sha256' => '5e00adb9a4cea134611ff94191400b049a8f76a6665bfe1038e98e1c48cb10cf',
        'archive' => 'Lato,Source_Serif_4.zip',
        'entry' => 'Source_Serif_4/static/SourceSerif4-Italic.ttf',
    ],
    [
        'target' => 'SourceSerif4-SemiBold.ttf',
        'sha256' => 'f8ca116c25700ca7aafe73ca5c9939b48420d32c0dd8192dcac042c7373cbfd8',
        'archive' => 'Lato,Source_Serif_4.zip',
        'entry' => 'Source_Serif_4/static/SourceSerif4-SemiBold.ttf',
    ],
    [
        'target' => 'NeulandFont_2017.ttf',
        'sha256' => '3fd2d96041e988b4bcc150cb59d65dfe9423c542e763d04c92dc16796aaa2d42',
        'archive' => 'Neuland_Font_2017*.zip',
        'entry' => 'NeulandFont_2017.ttf',
    ],
];

if (! is_dir($targetDirectory) && ! mkdir($targetDirectory, 0775, true) && ! is_dir($targetDirectory)) {
    fwrite(STDERR, "Font-Verzeichnis konnte nicht angelegt werden.\n");
    exit(1);
}

function commandExists(string $command): bool
{
    $probe = PHP_OS_FAMILY === 'Windows'
        ? ['where', $command]
        : ['sh', '-lc', 'command -v '.escapeshellarg($command).' >/dev/null 2>&1'];

    $process = proc_open($probe, [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ], $pipes);

    if (! is_resource($process)) {
        return false;
    }

    foreach ($pipes as $pipe) {
        if (is_resource($pipe)) {
            fclose($pipe);
        }
    }

    return proc_close($process) === 0;
}

/** @return list<string> */
function archiveCandidates(string $root, string $pattern): array
{
    if (! str_contains($pattern, '*')) {
        $path = $root.DIRECTORY_SEPARATOR.$pattern;

        return is_file($path) ? [$path] : [];
    }

    $matches = glob($root.DIRECTORY_SEPARATOR.$pattern) ?: [];
    sort($matches);

    return array_values(array_filter($matches, 'is_file'));
}

function verifyFile(string $path, string $expectedHash): bool
{
    return is_file($path) && hash_file('sha256', $path) === $expectedHash;
}

function readZipEntry(string $archivePath, string $entry): string|false
{
    if (class_exists(ZipArchive::class)) {
        $zip = new ZipArchive;

        if ($zip->open($archivePath) !== true) {
            return false;
        }

        $bytes = $zip->getFromName($entry);
        $zip->close();

        return $bytes;
    }

    if (! commandExists('unzip')) {
        return false;
    }

    $process = proc_open(
        ['unzip', '-p', $archivePath, $entry],
        [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ],
        $pipes,
    );

    if (! is_resource($process)) {
        return false;
    }

    fclose($pipes[0]);
    $bytes = stream_get_contents($pipes[1]);
    stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);

    return proc_close($process) === 0 ? $bytes : false;
}

function writeAtomically(string $destination, string $content): bool
{
    $temporary = $destination.'.tmp';

    if (file_put_contents($temporary, $content, LOCK_EX) === false) {
        @unlink($temporary);

        return false;
    }

    if (! @rename($temporary, $destination)) {
        @unlink($destination);

        if (! @rename($temporary, $destination)) {
            @unlink($temporary);

            return false;
        }
    }

    return true;
}

function installFont(array $font, string $root, string $legacyDirectory, string $destination): bool
{
    $legacy = $legacyDirectory.DIRECTORY_SEPARATOR.$font['target'];

    if (verifyFile($legacy, $font['sha256'])) {
        $bytes = file_get_contents($legacy);

        return is_string($bytes) && writeAtomically($destination, $bytes);
    }

    if (! class_exists(ZipArchive::class) && ! commandExists('unzip')) {
        fwrite(STDERR, "Weder PHP ext-zip noch das Kommando unzip ist verfügbar.\n");

        return false;
    }

    foreach (archiveCandidates($root, $font['archive']) as $archivePath) {
        $content = readZipEntry($archivePath, $font['entry']);

        if (! is_string($content) || hash('sha256', $content) !== $font['sha256']) {
            continue;
        }

        return writeAtomically($destination, $content);
    }

    return false;
}

$failed = false;

foreach ($fonts as $font) {
    $destination = $targetDirectory.DIRECTORY_SEPARATOR.$font['target'];

    if (verifyFile($destination, $font['sha256'])) {
        fwrite(STDOUT, "OK  {$font['target']}\n");

        continue;
    }

    if ($checkOnly) {
        fwrite(STDERR, "FEHLT/ABWEICHEND  {$font['target']}\n");
        $failed = true;

        continue;
    }

    if (! installFont($font, $root, $legacyDirectory, $destination)
        || ! verifyFile($destination, $font['sha256'])) {
        fwrite(STDERR, "FEHLER  {$font['target']} - weder gültige Altdatei noch passende Quelle gefunden.\n");
        $failed = true;

        continue;
    }

    fwrite(STDOUT, "INSTALLIERT  {$font['target']}\n");
}

if ($failed) {
    fwrite(STDERR, "\nFont-Synchronisierung nicht vollständig. Altdateien und Quell-ZIPs werden nicht gelöscht.\n");
    exit(1);
}

if ($cleanup) {
    foreach ($fonts as $font) {
        $legacy = $legacyDirectory.DIRECTORY_SEPARATOR.$font['target'];

        if (is_file($legacy) && @unlink($legacy)) {
            fwrite(STDOUT, "ALTDATEI GELÖSCHT  public/fonts/vdbs/{$font['target']}\n");
        }
    }

    if (is_dir($legacyDirectory)) {
        @rmdir($legacyDirectory);
    }

    $archives = array_merge(
        archiveCandidates($root, 'Lato,Source_Serif_4.zip'),
        archiveCandidates($root, 'Neuland_Font_2017*.zip'),
    );

    foreach (array_unique($archives) as $archivePath) {
        if (@unlink($archivePath)) {
            fwrite(STDOUT, 'QUELL-ZIP GELÖSCHT  '.basename($archivePath)."\n");
        } else {
            fwrite(STDERR, 'WARNUNG  '.basename($archivePath)." konnte nicht gelöscht werden.\n");
        }
    }
}

fwrite(STDOUT, "\nVDBS-Fonts sind vollständig, prüfsummengeprüft und Vite-kompatibel eingebunden.\n");
