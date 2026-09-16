<?php

/**
 * Matomo - free/libre analytics platform
 *
 * Openmost Audit plugin — standalone unit-test bootstrap.
 *
 * This file makes the plugin's PHP classes available to PHPUnit without
 * requiring a full Matomo installation. It registers a minimal PSR-4
 * autoloader for the `Piwik\Plugins\Audit\` namespace and loads any
 * real Composer autoload.php if one is reachable (useful when tests are
 * executed from within a Matomo checkout).
 *
 * @link    https://openmost.com
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

$pluginRoot = dirname(__DIR__);
$matomoRoot = dirname($pluginRoot, 2);

// Prefer the real Matomo autoloader if present — it gives tests access
// to Piwik core classes when they need them.
$composer = $matomoRoot . '/vendor/autoload.php';
if (is_file($composer)) {
    require_once $composer;
}

spl_autoload_register(static function (string $class) use ($pluginRoot): void {
    $prefix = 'Piwik\\Plugins\\Audit\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $relative = str_replace('\\', DIRECTORY_SEPARATOR, $relative);
    $path     = $pluginRoot . DIRECTORY_SEPARATOR . $relative . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});
