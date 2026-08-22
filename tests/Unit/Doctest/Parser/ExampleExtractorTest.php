<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Parser;

use function iterator_to_array;

use PhpAiToolkit\Doctest\Parser\Example;
use PhpAiToolkit\Doctest\Parser\ExampleExtractor;
use PhpAiToolkit\Doctest\Scanner\Target;
use PhpAiToolkit\Doctest\Scanner\TargetKind;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExampleExtractor::class)]
#[UsesClass(Example::class)]
#[UsesClass(Target::class)]
final class ExampleExtractorTest extends TestCase
{
    public function testExtractReadsAtExampleBlocksBeforeCodeFences(): void
    {
        $docblock = "/**\n * Summary.\n *\n * ```php\n * fenced();\n * ```\n *\n * @example Tagged\n * tagged();\n */";
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', $docblock, 'Widget', 4);

        $examples = iterator_to_array((new ExampleExtractor())->extract($target), false);

        self::assertCount(2, $examples);
        self::assertSame('tagged();', $examples[0]->code);
        self::assertSame('Tagged', $examples[0]->description);
        self::assertSame(0, $examples[0]->index);
        self::assertSame('fenced();', $examples[1]->code);
        self::assertNull($examples[1]->description);
        self::assertSame(1, $examples[1]->index);
    }

    public function testExtractSkipsATagThatCarriesNoCode(): void
    {
        $docblock = "/**\n * @example \$widget->render()\n */";
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', $docblock, 'Widget', 4);

        self::assertSame([], iterator_to_array((new ExampleExtractor())->extract($target), false));
    }

    public function testCleanDocblockStripsTheFrameAndOneLeadingSpacePerLine(): void
    {
        self::assertSame("Summary.\n  indented", (new ExampleExtractor())->cleanDocblock("/**\n * Summary.\n *   indented\n */"));
    }

    public function testExtractExampleTagsStopsAtTheNextTag(): void
    {
        $cleaned = "@example Adding\n\n\$sum = add(1, 2);\n\$sum // => 3\n\n@param int \$x\n";
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Widget', 4);
        $index = 0;

        $examples = iterator_to_array((new ExampleExtractor())->extractExampleTags($cleaned, $target, $index), false);

        self::assertCount(1, $examples);
        self::assertSame("\$sum = add(1, 2);\n\$sum // => 3", $examples[0]->code);
        self::assertSame(1, $index);
    }

    public function testTagDescriptionReadsTheTextOnTheTagLine(): void
    {
        $extractor = new ExampleExtractor();

        self::assertSame('Adding numbers', $extractor->tagDescription("@example Adding numbers\nadd(1, 2)"));
        self::assertNull($extractor->tagDescription("@example\nadd(1, 2)"));
    }

    public function testTagCodeReadsTheLinesBelowTheTag(): void
    {
        $extractor = new ExampleExtractor();

        self::assertSame('add(1, 2)', $extractor->tagCode("@example Adding\n\nadd(1, 2)\n\n"));
        self::assertSame('', $extractor->tagCode('@example Adding'));
    }

    public function testExtractCodeFencesMatchesOnlyBarePhpFences(): void
    {
        $cleaned = "```php\necho 1;\n```\n\n```php ignore\necho 2;\n```\n\n```\necho 3;\n```";
        $target = new Target(TargetKind::CLASS_LIKE, '/a.php', '/** */', 'Widget', 4);
        $index = 0;

        $examples = iterator_to_array((new ExampleExtractor())->extractCodeFences($cleaned, $target, $index), false);

        self::assertCount(1, $examples);
        self::assertSame('echo 1;', $examples[0]->code);
    }

    public function testCalculateLineNumberCountsTheNewlinesBeforeTheOffset(): void
    {
        self::assertSame(13, (new ExampleExtractor())->calculateLineNumber("a\nb\nc", 4, 10));
    }
}
