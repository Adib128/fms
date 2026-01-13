<?php
$root = __DIR__;

$excluded = [
    'app/security.php',
    'cleanup_security.php',
    'config.php',
];

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));

foreach ($iterator as $file) {
    if (!$file->isFile()) {
        continue;
    }

    if ($file->getExtension() !== 'php') {
        continue;
    }

    $relativePath = str_replace($root . DIRECTORY_SEPARATOR, '', $file->getPathname());

    if (in_array($relativePath, $excluded, true)) {
        continue;
    }

    $content = file_get_contents($file->getPathname());
    $updated = $content;

    // Collapse duplicate PHP opening tags
    $updated = preg_replace('/^<\?php\s*<\?php/s', '<?php', $updated, 1);

    // Remove the injected security guard block
    $updated = preg_replace(
        '/\/+ Security check - must be included before any output\s*if \(!defined\(\'ROUTED\'\)\) {\s*require_once __DIR__ \. \'\/app\/security\.php\';\s*}\s*/m',
        '',
        $updated,
        1
    );

    // Clean leading whitespace after PHP opening tag
    $updated = preg_replace('/^<\?php\s*\n\s*\n/', "<?php\n", $updated, 1);

    if ($updated !== $content) {
        file_put_contents($file->getPathname(), $updated);
        echo "Cleaned: {$relativePath}\n";
    }
}

echo "Cleanup complete.\n";
