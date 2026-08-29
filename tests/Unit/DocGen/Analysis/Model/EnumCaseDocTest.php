<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Model\DocBlock;
use Toolkit\DocGen\Analysis\Model\EnumCaseDoc;

/**
 * @covers \Toolkit\DocGen\Analysis\Model\EnumCaseDoc
 * @uses \Toolkit\DocGen\Analysis\Model\DocBlock
 */
#[CoversClass(EnumCaseDoc::class)]
#[UsesClass(DocBlock::class)]
#[UsesClass(\Toolkit\Mutation\MutationContract::class)]
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
