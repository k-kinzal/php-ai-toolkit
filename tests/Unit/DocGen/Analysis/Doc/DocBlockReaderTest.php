<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Doc;

use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\DocTag;
use PhpAiToolkit\DocGen\Analysis\Model\TemplateDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeAliasDoc;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\DocBlock
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\DocTag
 * @uses \PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\TemplateDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\TypeAliasDoc
 */
#[CoversClass(DocBlockReader::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(DocTag::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(TemplateDoc::class)]
#[UsesClass(TypeAliasDoc::class)]
final class DocBlockReaderTest extends TestCase
{
    public function testReadReturnsNullForMissingOrBlankComment(): void
    {
        self::assertNull((new DocBlockReader())->read(null));
        self::assertNull((new DocBlockReader())->read('   '));
    }

    public function testVisibilityReadsEveryDeclaredScope(): void
    {
        $comment = <<<'DOC'
/**
 * Summary line.
 *
 * @visibility namespace
 * @visibility Demo\Console
 */
DOC;

        self::assertSame(['namespace', 'Demo\\Console'], (new DocBlockReader())->read($comment)?->visibility);
    }

    public function testVisibilityReturnsNothingWithoutTheTag(): void
    {
        self::assertSame([], (new DocBlockReader())->read('/** Summary line. */')?->visibility);
    }

    public function testReadSplitsSummaryAndDescriptionOnBlankLine(): void
    {
        $comment = <<<'DOC'
/**
 * Summary line.
 *
 * Description first.
 * Description second.
 */
DOC;

        $doc = (new DocBlockReader())->read($comment);

        self::assertSame('Summary line.', $doc?->summary);
        self::assertSame("Description first.\nDescription second.", $doc->description);
    }

    public function testReadCollectsTagsIntoTheDocBlockModel(): void
    {
        $comment = <<<'DOC'
/**
 * Widget summary.
 *
 * @param int $x the input
 * @return string
 * @throws RuntimeException on failure
 * @template T
 * @phpstan-type Pair array{int, int}
 * @implements Rule<T>
 * @deprecated use Gadget instead
 * @internal
 */
DOC;

        $doc = (new DocBlockReader())->read($comment);

        self::assertSame('Widget summary.', $doc?->summary);
        self::assertSame('', $doc->description);
        self::assertSame(['$x'], array_keys($doc->params));
        self::assertNotNull($doc->return);
        self::assertNull($doc->var);
        self::assertCount(1, $doc->throws);
        self::assertCount(1, $doc->templates);
        self::assertCount(1, $doc->aliases);
        self::assertCount(1, $doc->implementsTags);
        self::assertSame([], $doc->extendsTags);
        self::assertSame([], $doc->usesTags);
        self::assertSame('use Gadget instead', $doc->deprecated);
        self::assertTrue($doc->internal);
        self::assertSame($comment, $doc->raw);
    }

    public function testTextSplitsFreeTextIntoSummaryAndDescription(): void
    {
        $node = (new PhpDocParserBridge())->parse(<<<'DOC'
/**
 * Summary line.
 *
 * Description first.
 * Description second.
 */
DOC);

        self::assertSame(['Summary line.', "Description first.\nDescription second."], (new DocBlockReader())->text($node));
    }

    public function testTextReturnsEmptyStringsForTagOnlyComment(): void
    {
        $node = (new PhpDocParserBridge())->parse('/** @param int $x */');

        self::assertSame(['', ''], (new DocBlockReader())->text($node));
    }

    public function testParamsPrefersPhpstanOverPsalmOverStandardTags(): void
    {
        $node = (new PhpDocParserBridge())->parse(<<<'DOC'
/**
 * @param int $x standard note
 * @psalm-param string $x
 * @phpstan-param bool $x precise note
 * @param float $y y note
 */
DOC);

        $params = (new DocBlockReader())->params($node);

        self::assertSame(['$x', '$y'], array_keys($params));
        self::assertSame('bool', (string) $params['$x']->type);
        self::assertSame('precise note', $params['$x']->description);
        self::assertSame('float', (string) $params['$y']->type);
        self::assertSame('y note', $params['$y']->description);
    }

    public function testReturnTagPrefersTheMostPreciseTag(): void
    {
        $reader = new DocBlockReader();
        $node = (new PhpDocParserBridge())->parse("/**\n * @return int base\n * @phpstan-return positive-int precise\n */");

        $tag = $reader->returnTag($node);

        self::assertSame('positive-int', (string) $tag?->type);
        self::assertSame('precise', $tag?->description);
        self::assertNull($reader->returnTag((new PhpDocParserBridge())->parse('/** Nothing here. */')));
    }

    public function testVarTagPrefersPsalmOverStandardTags(): void
    {
        $reader = new DocBlockReader();
        $node = (new PhpDocParserBridge())->parse("/**\n * @var int old\n * @psalm-var string better\n */");

        $tag = $reader->varTag($node);

        self::assertSame('string', (string) $tag?->type);
        self::assertSame('better', $tag?->description);
        self::assertNull($reader->varTag((new PhpDocParserBridge())->parse('/** Nothing here. */')));
    }

    public function testThrowsTagsCollectsEveryThrowsTag(): void
    {
        $node = (new PhpDocParserBridge())->parse("/**\n * @throws RuntimeException when broken\n * @throws LogicException\n */");

        $tags = (new DocBlockReader())->throwsTags($node);

        self::assertCount(2, $tags);
        self::assertSame('RuntimeException', (string) $tags[0]->type);
        self::assertSame('when broken', $tags[0]->description);
        self::assertSame('LogicException', (string) $tags[1]->type);
        self::assertSame('', $tags[1]->description);
    }

    public function testTemplatesCollectsCovariantTemplatesAndKeepsTheFirstDeclaration(): void
    {
        $node = (new PhpDocParserBridge())->parse(<<<'DOC'
/**
 * @template T of object bounded note
 * @template-covariant U
 * @phpstan-template T of string
 */
DOC);

        $templates = (new DocBlockReader())->templates($node);

        self::assertCount(2, $templates);
        self::assertSame('T', $templates[0]->name);
        self::assertSame('object', (string) $templates[0]->bound);
        self::assertSame('bounded note', $templates[0]->description);
        self::assertSame('U', $templates[1]->name);
        self::assertNull($templates[1]->bound);
        self::assertSame('', $templates[1]->description);
    }

    public function testAliasesCollectsLocalAndImportedTypeAliases(): void
    {
        $node = (new PhpDocParserBridge())->parse(<<<'DOC'
/**
 * @phpstan-type Coords array{x: int, y: int}
 * @phpstan-import-type Row from \Vendor\Rows
 * @phpstan-import-type Col from Grid as LocalCol
 */
DOC);

        $aliases = (new DocBlockReader())->aliases($node);

        self::assertCount(3, $aliases);
        self::assertSame('Coords', $aliases[0]->name);
        self::assertSame('array{x: int, y: int}', (string) $aliases[0]->type);
        self::assertNull($aliases[0]->importedFrom);
        self::assertSame('Row', $aliases[1]->name);
        self::assertNull($aliases[1]->type);
        self::assertSame('\Vendor\Rows', $aliases[1]->importedFrom);
        self::assertSame('LocalCol', $aliases[2]->name);
        self::assertNull($aliases[2]->type);
        self::assertSame('Grid', $aliases[2]->importedFrom);
    }

    public function testRelationTagsCollectsImplementsRelations(): void
    {
        $node = (new PhpDocParserBridge())->parse('/** @implements Rule<TNodeType> */');

        $tags = (new DocBlockReader())->relationTags($node, ['@implements', '@phpstan-implements', '@template-implements']);

        self::assertCount(1, $tags);
        self::assertSame('Rule<TNodeType>', (string) $tags[0]->type);
        self::assertSame('', $tags[0]->description);
    }

    public function testDeprecatedReturnsNoteEmptyStringOrNull(): void
    {
        $reader = new DocBlockReader();
        $bridge = new PhpDocParserBridge();

        self::assertSame('use Foo instead', $reader->deprecated($bridge->parse('/** @deprecated use Foo instead */')));
        self::assertSame('', $reader->deprecated($bridge->parse('/** @deprecated */')));
        self::assertNull($reader->deprecated($bridge->parse('/** Nothing here. */')));
    }
}
