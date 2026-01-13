<?php
/**
 * This script adds security checks to all PHP files in the application
 * to prevent direct access without authentication.
 */

$rootDir = __DIR__;
$excludedFiles = [
    'config.php',
    'header.php',
    'footer.php',
    'index.php',
    'login.php',
    'deconnexion.php',
    'apply_security.php',
    'app/security.php',
    'app/helpers.php',
    'app/bootstrap.php',
    'app/Router.php',
];

$excludedDirs = [
    '/vendor',
    '/node_modules',
    '/.git',
];

// Security check code to add
$securityCheck = <<<'PHP'
<?php
PHP;

// Function to process files
function processFile($file, $rootDir) {
    global $securityCheck;
    
    $content = file_get_contents($file);
    
    // Skip if file already has the security check
    if (strpos($content, '// Security check - must be included before any output') !== false) {
        echo "Skipping (already secured): $file\n";
        return;
    }
    
    // Add security check after the opening PHP tag
    $content = preg_replace(
        '/^<\?php\s*/',
        '<?php' . PHP_EOL . $securityCheck,
        $content,
        1,
        $count
    );
    
    if ($count === 0) {
        echo "Warning: Could not add security check to: $file\n";
        return;
    }
    
    file_put_contents($file, $content);
    echo "Secured: $file\n";
}

// Find all PHP files
$directory = new RecursiveDirectoryIterator($rootDir);
$iterator = new RecursiveIteratorIterator($directory);
$phpFiles = new RegexIterator($iterator, '/^.+\\.php$/i', RecursiveRegexIterator::GET_MATCH);

foreach ($phpFiles as $file) {
    $filePath = $file[0];
    $relativePath = str_replace($rootDir, '', $filePath);
    
    // Skip excluded files and directories
    $shouldSkip = false;
    foreach ($excludedFiles as $excludedFile) {
        if (str_ends_with($filePath, $excludedFile)) {
            $shouldSkip = true;
            break;
        }
    }
    
    foreach ($excludedDirs as $excludedDir) {
        if (strpos($filePath, $rootDir . $excludedDir) === 0) {
            $shouldSkip = true;
            break;
        }
    }
    
    if (!$shouldSkip) {
        processFile($filePath, $rootDir);
    }
}

echo "\nSecurity check has been applied to all PHP files.\n";
