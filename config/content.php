<?php

declare(strict_types=1);

return [
    /*
     * Lizenzschlüssel des Texteditors (TinyMCE, selbst gehostet). „gpl“ nutzt die Open-Source-Lizenz GPL-2.0-or-later;
     * mit einer gekauften Lizenz steht hier der Schlüssel (TINYMCE_LICENSE_KEY in der .env).
     */
    'editor_license_key' => env('TINYMCE_LICENSE_KEY', 'gpl'),
];
