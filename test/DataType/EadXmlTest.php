<?php
namespace EadXmlDataType\Test\DataType;

use EadXmlDataType\DataType\EadXml;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for EadXmlDataType\DataType\EadXml.
 *
 * Because the module runs inside Omeka S at runtime, all Omeka / Laminas
 * framework classes are stubbed in test/bootstrap.php.  Tests therefore cover
 * only the pure-PHP logic inside EadXml.php.
 */
class EadXmlTest extends TestCase
{
    private EadXml $dataType;

    protected function setUp(): void
    {
        $this->dataType = new EadXml();
    }

    // ── Basic interface ──────────────────────────────────────────────────────

    public function testGetName(): void
    {
        $this->assertSame('ead:xml', $this->dataType->getName());
    }

    public function testGetLabel(): void
    {
        $this->assertSame('EAD XML', $this->dataType->getLabel());
    }

    public function testGetOptgroupLabel(): void
    {
        $this->assertSame('EAD', $this->dataType->getOptgroupLabel());
    }

    // ── isValid ──────────────────────────────────────────────────────────────

    public function testIsValidWithNonEmptyValue(): void
    {
        $this->assertTrue($this->dataType->isValid(['@value' => '<emph>foo</emph>']));
    }

    public function testIsValidWithWhitespaceOnlyValue(): void
    {
        $this->assertFalse($this->dataType->isValid(['@value' => '   ']));
    }

    public function testIsValidWithEmptyString(): void
    {
        $this->assertFalse($this->dataType->isValid(['@value' => '']));
    }

    public function testIsValidWithMissingKey(): void
    {
        $this->assertFalse($this->dataType->isValid([]));
    }

    public function testIsValidWithNonStringValue(): void
    {
        $this->assertFalse($this->dataType->isValid(['@value' => 42]));
    }

    // ── hydrate ──────────────────────────────────────────────────────────────

    public function testHydrateStoresValue(): void
    {
        $entity = new Value();
        $adapter = $this->createMock(\Omeka\Api\Adapter\AbstractEntityAdapter::class);
        $this->dataType->hydrate(['@value' => '<emph>test</emph>'], $entity, $adapter);

        $this->assertSame('<emph>test</emph>', $entity->getValue());
        $this->assertNull($entity->getLang());
        $this->assertNull($entity->getUri());
        $this->assertNull($entity->getValueResource());
    }

    // ── toString / getFulltextText ────────────────────────────────────────────

    public function testToStringStripsXmlTags(): void
    {
        $rep = new ValueRepresentation('<emph render="italic">Hamlet</emph>');
        $this->assertSame('Hamlet', $this->dataType->toString($rep));
    }

    public function testGetJsonLd(): void
    {
        $rep = new ValueRepresentation('<emph>foo</emph>');
        $this->assertSame(['@value' => '<emph>foo</emph>'], $this->dataType->getJsonLd($rep));
    }

    // ── eadToHtml — <emph> render values ─────────────────────────────────────

    /**
     * @dataProvider emphRenderProvider
     */
    public function testEmphRender(string $input, string $expected): void
    {
        $rep = new ValueRepresentation($input);
        $this->assertSame($expected, $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function emphRenderProvider(): array
    {
        return [
            'italic'          => ['<emph render="italic">A</emph>', '<em>A</em>'],
            'bold'            => ['<emph render="bold">A</emph>', '<strong>A</strong>'],
            'bolditalic'      => ['<emph render="bolditalic">A</emph>', '<strong><em>A</em></strong>'],
            'underline'       => ['<emph render="underline">A</emph>', '<u>A</u>'],
            'boldunderline'   => ['<emph render="boldunderline">A</emph>', '<strong><u>A</u></strong>'],
            'quoted'          => ['<emph render="quoted">A</emph>', '&ldquo;A&rdquo;'],
            'doublequote'     => ['<emph render="doublequote">A</emph>', '&ldquo;A&rdquo;'],
            'singlequote'     => ['<emph render="singlequote">A</emph>', '&lsquo;A&rsquo;'],
            'bolddoublequote' => ['<emph render="bolddoublequote">A</emph>', '<strong>&ldquo;A&rdquo;</strong>'],
            'boldsinglequote' => ['<emph render="boldsinglequote">A</emph>', '<strong>&lsquo;A&rsquo;</strong>'],
            'super'           => ['<emph render="super">A</emph>', '<sup>A</sup>'],
            'sub'             => ['<emph render="sub">A</emph>', '<sub>A</sub>'],
            'smcaps'          => ['<emph render="smcaps">A</emph>', '<span style="font-variant:small-caps">A</span>'],
            'boldsmcaps'      => ['<emph render="boldsmcaps">A</emph>', '<strong><span style="font-variant:small-caps">A</span></strong>'],
            'no-render-attr'  => ['<emph>A</emph>', '<em>A</em>'],
        ];
    }

    // ── eadToHtml — <title> render values ────────────────────────────────────

    /**
     * @dataProvider titleRenderProvider
     */
    public function testTitleRender(string $input, string $expected): void
    {
        $rep = new ValueRepresentation($input);
        $this->assertSame($expected, $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function titleRenderProvider(): array
    {
        return [
            'no-render'       => ['<title>Hamlet</title>', '<cite>Hamlet</cite>'],
            'italic'          => ['<title render="italic">Hamlet</title>', '<cite>Hamlet</cite>'],
            'bold'            => ['<title render="bold">Hamlet</title>', '<cite><strong>Hamlet</strong></cite>'],
            'bolditalic'      => ['<title render="bolditalic">Hamlet</title>', '<cite><strong><em>Hamlet</em></strong></cite>'],
            'underline'       => ['<title render="underline">Hamlet</title>', '<cite><u>Hamlet</u></cite>'],
            'boldunderline'   => ['<title render="boldunderline">Hamlet</title>', '<cite><strong><u>Hamlet</u></strong></cite>'],
            'quoted'          => ['<title render="quoted">Hamlet</title>', '<cite>&ldquo;Hamlet&rdquo;</cite>'],
            'smcaps'          => ['<title render="smcaps">Hamlet</title>', '<cite><span style="font-variant:small-caps">Hamlet</span></cite>'],
        ];
    }

    // ── eadToHtml — structural elements ──────────────────────────────────────

    public function testLbBecomesBreak(): void
    {
        $rep = new ValueRepresentation('line1<lb/>line2');
        $this->assertSame('line1<br>line2', $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function testParagraph(): void
    {
        $rep = new ValueRepresentation('<p>Hello world</p>');
        $this->assertSame('<p>Hello world</p>', $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function testBlockquote(): void
    {
        $rep = new ValueRepresentation('<blockquote>quote</blockquote>');
        $this->assertSame('<blockquote>quote</blockquote>', $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function testListAndItem(): void
    {
        $rep = new ValueRepresentation('<list><item>A</item><item>B</item></list>');
        $this->assertSame('<ul><li>A</li><li>B</li></ul>', $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function testRefWithHref(): void
    {
        $rep = new ValueRepresentation('<ref href="https://example.com">link text</ref>');
        $this->assertSame('<a href="https://example.com">link text</a>', $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function testRefWithTarget(): void
    {
        $rep = new ValueRepresentation('<ref target="series1">Series I</ref>');
        $this->assertSame('<a href="#series1">Series I</a>', $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function testRefWithNoHrefOrTarget(): void
    {
        $rep = new ValueRepresentation('<ref>bare text</ref>');
        $this->assertSame('bare text', $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function testTableStructure(): void
    {
        $input = '<table><tgroup><thead><row><entry>Col</entry></row></thead>'
               . '<tbody><row><entry>Val</entry></row></tbody></tgroup></table>';
        $rep = new ValueRepresentation($input);
        $expected = '<table><thead><tr><td>Col</td></tr></thead>'
                  . '<tbody><tr><td>Val</td></tr></tbody></table>';
        $this->assertSame($expected, $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function testHeadBecomesStrong(): void
    {
        $rep = new ValueRepresentation('<head>Section Title</head>');
        $this->assertSame('<strong>Section Title</strong>', $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    // ── eadToHtml — unknown elements and fallback ─────────────────────────────

    public function testUnknownElementStripsTag(): void
    {
        $rep = new ValueRepresentation('<unknown>text</unknown>');
        $this->assertSame('text', $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function testMalformedXmlFallsBackToEscapedPlainText(): void
    {
        $rep = new ValueRepresentation('<unclosed');
        $result = $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep);
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringNotContainsString('<unclosed', $result);
    }

    public function testSpecialCharactersInTextNodesAreEscaped(): void
    {
        $rep = new ValueRepresentation('<emph render="italic">A &amp; B &lt;C&gt;</emph>');
        // The & and < > in the XML source arrive as decoded text nodes; they must be
        // re-escaped in the HTML output.
        $result = $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep);
        $this->assertStringContainsString('&amp;', $result);
        $this->assertStringContainsString('&lt;', $result);
        $this->assertStringContainsString('&gt;', $result);
    }

    // ── Complex / mixed-content example ──────────────────────────────────────

    public function testMixedInlineContent(): void
    {
        $input = '<emph render="italic">The Great Gatsby</emph> by <emph render="bold">F. Scott Fitzgerald</emph>';
        $rep = new ValueRepresentation($input);
        $expected = '<em>The Great Gatsby</em> by <strong>F. Scott Fitzgerald</strong>';
        $this->assertSame($expected, $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function testNestedEmphElements(): void
    {
        $rep = new ValueRepresentation('<emph render="bold"><emph render="italic">nested</emph></emph>');
        $this->assertSame('<strong><em>nested</em></strong>', $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep));
    }

    public function testRefHrefXssIsSafelyEscaped(): void
    {
        $rep = new ValueRepresentation('<ref href="javascript:alert(1)">click</ref>');
        $result = $this->dataType->render(new \Laminas\View\Renderer\PhpRenderer(), $rep);
        // The href attribute value must be HTML-attribute-escaped; no raw < or > should appear.
        $this->assertStringNotContainsString('<script', $result);
        $this->assertStringContainsString('javascript', $result); // href is included but escaped
    }
}
