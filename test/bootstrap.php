<?php
/**
 * PHPUnit bootstrap for EadXmlDataType.
 *
 * Loads Omeka S / Laminas stubs so the module can be tested without a running
 * Omeka S installation, then registers an autoloader for the module source.
 */

$stubDir = __DIR__ . '/stub';
require_once $stubDir . '/OmekaDataType.php';
require_once $stubDir . '/OmekaEntity.php';
require_once $stubDir . '/OmekaApiAdapter.php';
require_once $stubDir . '/OmekaApiRepresentation.php';
require_once $stubDir . '/LaminasRenderer.php';

// Autoloader for EadXmlDataType\… → src/…
spl_autoload_register(function (string $class): void {
    if (strpos($class, 'EadXmlDataType\\') !== 0) {
        return;
    }
    $relative = substr($class, strlen('EadXmlDataType\\'));
    $file = __DIR__ . '/../src/' . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});
