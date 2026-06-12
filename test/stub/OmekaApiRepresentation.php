<?php
namespace Omeka\Api\Representation;

class ValueRepresentation
{
    private string $raw;

    public function __construct(string $raw)
    {
        $this->raw = $raw;
    }

    public function value(): string
    {
        return $this->raw;
    }
}
