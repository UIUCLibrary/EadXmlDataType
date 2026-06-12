<?php
namespace Omeka\Entity;

class Value
{
    private $textValue = null;
    private $lang = null;
    private $uri = null;
    private $valueResource = null;

    public function getValue() { return $this->textValue; }
    public function setValue($v) { $this->textValue = $v; }
    public function getLang() { return $this->lang; }
    public function setLang($v) { $this->lang = $v; }
    public function getUri() { return $this->uri; }
    public function setUri($v) { $this->uri = $v; }
    public function getValueResource() { return $this->valueResource; }
    public function setValueResource($v) { $this->valueResource = $v; }
}
