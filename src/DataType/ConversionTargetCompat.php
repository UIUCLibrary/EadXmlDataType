<?php
/**
 * Compatibility shim for Omeka\DataType\ConversionTargetInterface.
 *
 * ConversionTargetInterface was introduced in Omeka S 4.2.0 (December 2024).
 * This file defines a stub in the Omeka\DataType namespace so that the
 * EadXml class can declare it as an implemented interface regardless of the
 * Omeka S version that is installed.  When the real interface is already
 * present (Omeka S >= 4.2.0) this file has no effect.
 */

namespace Omeka\DataType;

use Omeka\Entity\Value;

if (!interface_exists(\Omeka\DataType\ConversionTargetInterface::class)) {
    interface ConversionTargetInterface
    {
        /**
         * Convert a value to the target data type.
         *
         * Return true if the conversion was successfully done. Return false if
         * the conversion was not possible.
         *
         * @param Value  $valueObject
         * @param string $dataTypeTarget
         * @return bool
         */
        public function convert(Value $valueObject, string $dataTypeTarget): bool;
    }
}
