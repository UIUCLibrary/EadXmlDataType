<?php
namespace EadXmlDataType\DataType;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\DataType\DataTypeInterface;
use Omeka\Entity\Value;
use Laminas\View\Renderer\PhpRenderer;

class EadXml implements DataTypeInterface
{
    public function getName()
    {
        return 'ead:xml';
    }

    public function getLabel()
    {
        return 'EAD XML'; // @translate
    }

    public function getOptgroupLabel()
    {
        return 'EAD'; // @translate
    }

    public function prepareForm(PhpRenderer $view)
    {
        // No special JS assets needed.
    }

    public function form(PhpRenderer $view)
    {
        return $view->partial('common/data-type/ead-xml');
    }

    public function isValid(array $valueObject)
    {
        return isset($valueObject['@value']) && is_string($valueObject['@value']) && '' !== trim($valueObject['@value']);
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $value->setValue($valueObject['@value']);
        $value->setLang(null);
        $value->setUri(null);
        $value->setValueResource(null);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        return $this->eadToHtml($value->value());
    }

    public function toString(ValueRepresentation $value)
    {
        return strip_tags($value->value());
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return ['@value' => $value->value()];
    }

    public function getFulltextText(PhpRenderer $view, ValueRepresentation $value)
    {
        return strip_tags($value->value());
    }

    /**
     * Convert an EAD XML fragment string to safe HTML.
     *
     * The input is wrapped in a root element for parsing. Known EAD elements
     * are mapped to their HTML equivalents; unknown elements are stripped and
     * only their text content / child output is preserved. All text nodes are
     * HTML-escaped before output.
     *
     * @param string $ead
     * @return string
     */
    protected function eadToHtml(string $ead): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        // Suppress parse errors for partial/malformed fragments.
        $xml = '<?xml version="1.0" encoding="UTF-8"?><root>' . $ead . '</root>';
        if (!@$doc->loadXML($xml)) {
            // Fall back to escaped plain text if the fragment cannot be parsed.
            return htmlspecialchars($ead, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $root = $doc->documentElement;
        return $this->convertChildren($root);
    }

    /**
     * Recursively convert child nodes of a DOMElement to HTML.
     *
     * @param \DOMElement|\DOMNode $node
     * @return string
     */
    private function convertChildren(\DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $this->convertNode($child);
        }
        return $html;
    }

    /**
     * Convert a single DOMNode to its HTML representation.
     *
     * @param \DOMNode $node
     * @return string
     */
    private function convertNode(\DOMNode $node): string
    {
        if ($node->nodeType === XML_TEXT_NODE) {
            return htmlspecialchars($node->nodeValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        /** @var \DOMElement $node */
        $tagName = strtolower($node->nodeName);
        $inner = $this->convertChildren($node);

        switch ($tagName) {
            case 'emph':
                $render = $node->getAttribute('render');
                switch ($render) {
                    case 'italic':
                        return '<em>' . $inner . '</em>';
                    case 'bold':
                        return '<strong>' . $inner . '</strong>';
                    case 'bolditalic':
                        return '<strong><em>' . $inner . '</em></strong>';
                    case 'underline':
                        return '<u>' . $inner . '</u>';
                    case 'boldunderline':
                        return '<strong><u>' . $inner . '</u></strong>';
                    case 'quoted':
                        return '&ldquo;' . $inner . '&rdquo;';
                    case 'super':
                        return '<sup>' . $inner . '</sup>';
                    case 'sub':
                        return '<sub>' . $inner . '</sub>';
                    default:
                        return '<em>' . $inner . '</em>';
                }

            case 'title':
                return '<cite>' . $inner . '</cite>';

            case 'lb':
                return '<br>';

            case 'blockquote':
                return '<blockquote>' . $inner . '</blockquote>';

            case 'list':
                return '<ul>' . $inner . '</ul>';

            case 'item':
                return '<li>' . $inner . '</li>';

            default:
                // Unknown element: strip the tag, preserve content.
                return $inner;
        }
    }
}
