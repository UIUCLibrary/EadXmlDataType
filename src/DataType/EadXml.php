<?php
namespace EadXmlDataType\DataType;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\DataType\ConversionTargetInterface;
use Omeka\DataType\DataTypeInterface;
use Omeka\Entity\Value;
use Laminas\View\Renderer\PhpRenderer;

class EadXml implements DataTypeInterface, ConversionTargetInterface
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
     * Convert an existing value to the ead:xml data type.
     *
     * Implements ConversionTargetInterface so that this data type appears in
     * the "Convert data type" dropdown on the Omeka S batch-edit form and can
     * receive values converted from other types (e.g. literal).
     *
     * Conversion succeeds whenever the source value carries a non-empty text
     * string (getValue()). URI, language, and value-resource fields are
     * cleared because ead:xml does not use them.
     *
     * @param Value  $valueObject   The value entity being converted.
     * @param string $dataTypeTarget The target data type name (unused here).
     * @return bool True on success, false when there is nothing to convert.
     */
    public function convert(Value $valueObject, string $dataTypeTarget): bool
    {
        $textValue = $valueObject->getValue();
        if (!is_string($textValue) || '' === trim($textValue)) {
            return false;
        }
        // ead:xml stores only the raw text value; clear the other fields.
        $valueObject->setLang(null);
        $valueObject->setUri(null);
        $valueObject->setValueResource(null);
        return true;
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
        $xml = '<?xml version="1.0" encoding="UTF-8"?><root>'
             . $this->escapeBareAmpersands($ead) . '</root>';
        if (!@$doc->loadXML($xml)) {
            // Fall back to escaped plain text if the fragment cannot be parsed.
            return htmlspecialchars($ead, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $root = $doc->documentElement;
        return $this->convertChildren($root);
    }

    /**
     * Make ampersands that are not part of a reference parseable as XML.
     *
     * EAD exported from ArchivesSpace often carries URLs with a raw "&" in a
     * query string (e.g. href='…controlcard&id=1178'). That is not well-formed
     * XML, and one such character would otherwise send the whole value to the
     * escaped plain-text fallback, showing every tag in it literally.
     * HTML named entities such as &nbsp;, which XML does not define, become
     * the character they name.
     */
    private function escapeBareAmpersands(string $ead): string
    {
        return preg_replace_callback(
            '/&(?:(#[0-9]+|#x[0-9a-fA-F]+|[A-Za-z][A-Za-z0-9]*);)?/',
            function (array $match): string {
                if (!isset($match[1])) {
                    return '&amp;';
                }
                $reference = $match[1];
                if ($reference[0] === '#' || in_array($reference, ['amp', 'lt', 'gt', 'quot', 'apos'], true)) {
                    return $match[0];
                }
                $decoded = html_entity_decode($match[0], ENT_QUOTES | ENT_HTML5, 'UTF-8');
                return $decoded !== $match[0]
                    ? htmlspecialchars($decoded, ENT_XML1 | ENT_QUOTES, 'UTF-8')
                    : '&amp;' . $reference . ';';
            },
            $ead
        );
    }

    /**
     * Render an EAD linking element (<ref>, <extref>, <ptr>, <extptr>).
     *
     * The URL may be in href or, in namespaced EAD, xlink:href. Pointers are
     * empty elements, so they fall back to their title attribute, then to the
     * URL itself, for the link text.
     */
    private function convertLink(\DOMElement $node, string $inner): string
    {
        if ($inner === '') {
            $title = $node->getAttribute('title') ?: $node->getAttribute('xlink:title');
            $inner = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }

        $href = $node->getAttribute('href') ?: $node->getAttribute('xlink:href');
        if ($href !== '') {
            $safeHref = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<a href="' . $safeHref . '">' . ($inner !== '' ? $inner : $safeHref) . '</a>';
        }

        // No href: render as a named anchor if @target is set, or plain text.
        $target = $node->getAttribute('target');
        if ($target !== '') {
            $safeTarget = htmlspecialchars($target, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            return '<a href="#' . $safeTarget . '">' . $inner . '</a>';
        }
        return $inner;
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
            // ---------------------------------------------------------------
            // <emph> — inline emphasis
            // ---------------------------------------------------------------
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
                    case 'quoted':       // fall-through: both map to curly double quotes
                    case 'doublequote':
                        return '&ldquo;' . $inner . '&rdquo;';
                    case 'singlequote':
                        return '&lsquo;' . $inner . '&rsquo;';
                    case 'bolddoublequote':
                        return '<strong>&ldquo;' . $inner . '&rdquo;</strong>';
                    case 'boldsinglequote':
                        return '<strong>&lsquo;' . $inner . '&rsquo;</strong>';
                    case 'super':
                        return '<sup>' . $inner . '</sup>';
                    case 'sub':
                        return '<sub>' . $inner . '</sub>';
                    case 'smcaps':
                        return '<span style="font-variant:small-caps">' . $inner . '</span>';
                    case 'boldsmcaps':
                        return '<strong><span style="font-variant:small-caps">' . $inner . '</span></strong>';
                    default:
                        // No render attribute or unrecognised value → plain emphasis
                        return '<em>' . $inner . '</em>';
                }

            // ---------------------------------------------------------------
            // <title> — referenced work/publication title
            // render attributes follow the same pattern as <emph> but the
            // element is always wrapped in <cite> for semantic correctness.
            // ---------------------------------------------------------------
            case 'title':
                $render = $node->getAttribute('render');
                switch ($render) {
                    case 'bold':
                        return '<cite><strong>' . $inner . '</strong></cite>';
                    case 'bolditalic':
                        return '<cite><strong><em>' . $inner . '</em></strong></cite>';
                    case 'underline':
                        return '<cite><u>' . $inner . '</u></cite>';
                    case 'boldunderline':
                        return '<cite><strong><u>' . $inner . '</u></strong></cite>';
                    case 'quoted':
                    case 'doublequote':
                        return '<cite>&ldquo;' . $inner . '&rdquo;</cite>';
                    case 'singlequote':
                        return '<cite>&lsquo;' . $inner . '&rsquo;</cite>';
                    case 'bolddoublequote':
                        return '<cite><strong>&ldquo;' . $inner . '&rdquo;</strong></cite>';
                    case 'boldsinglequote':
                        return '<cite><strong>&lsquo;' . $inner . '&rsquo;</strong></cite>';
                    case 'super':
                        return '<cite><sup>' . $inner . '</sup></cite>';
                    case 'sub':
                        return '<cite><sub>' . $inner . '</sub></cite>';
                    case 'smcaps':
                        return '<cite><span style="font-variant:small-caps">' . $inner . '</span></cite>';
                    case 'boldsmcaps':
                        return '<cite><strong><span style="font-variant:small-caps">' . $inner . '</span></strong></cite>';
                    default:
                        // italic is the default/canonical rendering for a cited title
                        return '<cite>' . $inner . '</cite>';
                }

            // ---------------------------------------------------------------
            // <lb/> — line break
            // ---------------------------------------------------------------
            case 'lb':
                return '<br>';

            // ---------------------------------------------------------------
            // <p> — paragraph
            // ---------------------------------------------------------------
            case 'p':
                return '<p>' . $inner . '</p>';

            // ---------------------------------------------------------------
            // <blockquote>
            // ---------------------------------------------------------------
            case 'blockquote':
                return '<blockquote>' . $inner . '</blockquote>';

            // ---------------------------------------------------------------
            // <list> / <item> — unordered list
            // ---------------------------------------------------------------
            case 'list':
                return '<ul>' . $inner . '</ul>';

            case 'item':
                return '<li>' . $inner . '</li>';

            // ---------------------------------------------------------------
            // <ref> / <extref> — hyperlink with content
            // <ptr> / <extptr> — empty pointer
            // href (or xlink:href) → <a href="...">, target → <a href="#...">
            // ---------------------------------------------------------------
            case 'ref':
            case 'extref':
            case 'ptr':
            case 'extptr':
                return $this->convertLink($node, $inner);

            // ---------------------------------------------------------------
            // <table> / <tgroup> / <thead> / <tbody> / <row> / <entry>
            // ---------------------------------------------------------------
            case 'table':
                return '<table>' . $inner . '</table>';

            case 'tgroup':
                return $inner;

            case 'thead':
                return '<thead>' . $inner . '</thead>';

            case 'tbody':
                return '<tbody>' . $inner . '</tbody>';

            case 'row':
                return '<tr>' . $inner . '</tr>';

            case 'entry':
                return '<td>' . $inner . '</td>';

            // ---------------------------------------------------------------
            // <head> — section heading (inline context → bold)
            // ---------------------------------------------------------------
            case 'head':
                return '<strong>' . $inner . '</strong>';

            default:
                // Unknown / structural element: strip the tag, preserve content.
                return $inner;
        }
    }
}
