<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PhpAiToolkit\DocGen\Analysis\Model\DocBlock;
use PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Analysis\Model\EnumCaseDoc
 * @uses \PhpAiToolkit\DocGen\Analysis\Model\DocBlock
 */
#[CoversClass(EnumCaseDoc::class)]
#[UsesClass(DocBlock::class)]
final class EnumCaseDocTest extends TestCase
{
    public function testStoresDeclarationData(): void
    {
        $docBlock = new DocBlock('The active state.', '', [], null, null, [], [], [], [], [], [], null, false, '/** The active state. */');

        $case = new EnumCaseDoc('Active', "'active'", $docBlock, 7);

        self::assertSame('Active', $case->name);
        self::assertSame("'active'", $case->valueText);
        self::assertSame($docBlock, $case->docBlock);
        self::assertSame(7, $case->line);
    }

    public function testStoresAbsentOptionalsAsNull(): void
    {
        $case = new EnumCaseDoc('Pending', null, null, 9);

        self::assertSame('Pending', $case->name);
        self::assertNull($case->valueText);
        self::assertNull($case->docBlock);
        self::assertSame(9, $case->line);
    }
}
