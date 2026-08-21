<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Analysis;

use PhpAiToolkit\Doctest\Analysis\DocExample;
use PhpAiToolkit\Doctest\Analysis\DoctestExtractor;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(DoctestExtractor::class)]
#[UsesClass(DocExample::class)]
final class DoctestExtractorTest extends TestCase
{
    public function testCleanDocblockStripsFrameAndOneLeadingSpacePerLine(): void
    {
        $cleaned = (new DoctestExtractor())->cleanDocblock("/**\n * Summary.\n *   indented\n */");

        self::assertSame("Summary.\n  indented", $cleaned);
    }

    public function testTagExamplesCollectsMultiLineBodiesUntilTheNextTag(): void
    {
        $cleaned = <<<'TXT'
Intro text.
@example Adding numbers

$sum = add(1, 2);
$sum // => 3

@param int $x
TXT;

        $examples = (new DoctestExtractor())->tagExamples($cleaned);

        self::assertSame([
            [
                'description' => 'Adding numbers',
                'code' => '$sum = add(1, 2);' . "\n" . '$sum // => 3',
                'source' => 'tag',
            ],
        ], $examples);
    }

    public function testTagExamplesTurnsSingleLineTagsIntoInlineExamples(): void
    {
        $examples = (new DoctestExtractor())->tagExamples('@example add(1, 2) // comment');

        self::assertSame([
            [
                'description' => null,
                'code' => 'add(1, 2) // comment',
                'source' => 'tag-inline',
            ],
        ], $examples);
    }

    public function testFenceExamplesMatchesOnlyBarePhpFences(): void
    {
        $cleaned = <<<'TXT'
```php
echo 1;
```

```php ignore
echo 2;
```

```
echo 3;
```
TXT;

        self::assertSame(['echo 1;'], (new DoctestExtractor())->fenceExamples($cleaned));
    }

    public function testExtractOrdersTagExamplesBeforeFencesWithSequentialIndexes(): void
    {
        $comment = <<<'DOC'
/**
 * Summary.
 *
 * ```php
 * fenced();
 * ```
 *
 * @example
 * tagged();
 */
DOC;

        $examples = (new DoctestExtractor())->extract($comment);

        self::assertCount(2, $examples);
        self::assertSame('tag', $examples[0]->source);
        self::assertSame('tagged();', $examples[0]->code);
        self::assertNull($examples[0]->description);
        self::assertSame(0, $examples[0]->index);
        self::assertSame('fence', $examples[1]->source);
        self::assertSame('fenced();', $examples[1]->code);
        self::assertNull($examples[1]->description);
        self::assertSame(1, $examples[1]->index);
    }
}
