<?php
/**
 * Bootstrap — included at the top of every public PHP page
 * Usage: require_once __DIR__ . '/../../app/helpers/init.php';
 *        (adjust path depth as needed)
 */

// Compute root path relative to this file (app/helpers/init.php → project root is 2 levels up)
define('ROOT_PATH_TEMP', dirname(__DIR__, 2));

require_once ROOT_PATH_TEMP . '/app/config/config.php';

// Autoloader for App\ namespace (if vendor/autoload not available yet)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) return;

    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file     = APP_PATH . '/' . $relative . '.php';
    if (file_exists($file)) require_once $file;
});

// Composer autoloader (PHPMailer, mPDF, etc.)
$composerAutoload = ROOT_PATH . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Helpers
require_once APP_PATH . '/helpers/functions.php';

// Start session
\App\Core\Auth::startSession();

// Handle language switch
if (!empty($_GET['lang']) && in_array($_GET['lang'], ['th','en'])) {
    setLang($_GET['lang']);
    // Redirect to same page without ?lang= query
    $url = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['lang']);
    $qs = http_build_query($params);
    header('Location: ' . $url . ($qs ? '?' . $qs : ''));
    exit;
}

// Check session timeout for logged-in users
\App\Core\Auth::checkSessionTimeout();
