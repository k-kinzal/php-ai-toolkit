<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc;
use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Model\ConstantDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\DocBlock
 */
#[CoversClass(ConstantDoc::class)]
#[UsesClass(DocBlock::class)]
final class ConstantDocTest extends TestCase
{
    public function testStoresDeclarationData(): void
    {
        $docBlock = new DocBlock('The widget limit.', '', [], null, null, [], [], [], [], [], [], null, false, '/** The widget limit. */');

        $constant = new ConstantDoc('LIMIT', 'public', '10', $docBlock, 5);

        self::assertSame('LIMIT', $constant->name);
        self::assertSame('public', $constant->visibility);
        self::assertSame('10', $constant->valueText);
        self::assertSame($docBlock, $constant->docBlock);
        self::assertSame(5, $constant->line);
    }

    public function testStoresAbsentOptionalsAsNull(): void
    {
        $constant = new ConstantDoc('MODE', 'private', null, null, 8);

        self::assertSame('MODE', $constant->name);
        self::assertSame('private', $constant->visibility);
        self::assertNull($constant->valueText);
        self::assertNull($constant->docBlock);
        self::assertSame(8, $constant->line);
    }
}
