<?php
namespace EadXmlDataType;

use Omeka\Module\AbstractModule;
use Laminas\ModuleManager\ModuleManager;

// Load the compatibility shim before any class in this module is autoloaded
// so that Omeka\DataType\ConversionTargetInterface is always defined,
// even on Omeka S versions older than 4.2.0 that do not ship the interface.
require_once __DIR__ . '/src/DataType/ConversionTargetCompat.php';

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
