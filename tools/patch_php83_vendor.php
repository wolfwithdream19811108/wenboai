<?php

declare(strict_types=1);

/**
 * Apply the small PHP 8 compatibility fix still missing from think-image 1.x.
 *
 * The package uses the removed curly-brace string-offset syntax in its GIF
 * codec. Keep this as a Composer hook so a clean install remains runnable.
 */

$projectRoot = dirname(__DIR__);
$files = [
    $projectRoot . '/simplewind/vendor/topthink/think-image/src/image/gif/Decoder.php',
    $projectRoot . '/simplewind/vendor/topthink/think-image/src/image/gif/Encoder.php',
];

foreach ($files as $file) {
    if (!is_file($file)) {
        continue;
    }

    $source = file_get_contents($file);
    if ($source === false) {
        fwrite(STDERR, "Unable to read {$file}\n");
        exit(1);
    }

    $patched = preg_replace(
        '/(\$[A-Za-z_][A-Za-z0-9_]*(?:(?:->[A-Za-z_][A-Za-z0-9_]*)|(?:\[[^\]\r\n]+\]))*)\{([^{}\r\n]+)\}/',
        '$1[$2]',
        $source
    );

    if ($patched === null) {
        fwrite(STDERR, "Unable to patch {$file}\n");
        exit(1);
    }

    if ($patched !== $source && file_put_contents($file, $patched) === false) {
        fwrite(STDERR, "Unable to write {$file}\n");
        exit(1);
    }
}

fwrite(STDOUT, "Applied PHP 8.3 vendor compatibility patches.\n");
