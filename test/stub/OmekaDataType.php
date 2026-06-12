<?php
namespace Omeka\DataType;

interface DataTypeInterface {}

interface ConversionTargetInterface
{
    public function convert(\Omeka\Entity\Value $valueObject, string $dataTypeTarget): bool;
}
