<?php

declare(strict_types=1);

namespace Tests\Unit\Mutation;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\Mutation\MutationContract;
use Toolkit\Mutation\MutationContractReader;
use Toolkit\PhpStan\Rule\PhpDoc\RulePhpDocParser;

/**
 * @covers \Toolkit\Mutation\MutationContractReader
 */
#[CoversClass(MutationContractReader::class)]
#[UsesClass(MutationContract::class)]
#[UsesClass(RulePhpDocParser::class)]
final class MutationContractReaderTest extends TestCase
{
    public function testReadCollectsParameterReceiverAndGlobalEffects(): void
    {
        $node = (new RulePhpDocParser())->parse(<<<'DOC'
/**
 * @param object $value +mut updated value
 * @mutation $this, global
 */
DOC);
        $contract = (new MutationContractReader())->read($node);

        self::assertTrue($contract->mutatesParameter('value'));
        self::assertTrue($contract->mutatesThis());
        self::assertTrue($contract->mutatesGlobal());
        self::assertSame([], $contract->problems());
    }

    public function testReadReportsMisplacedAndUnknownSyntax(): void
    {
        $node = (new RulePhpDocParser())->parse(<<<'DOC'
/**
 * @param object $value prose +mut
 * @mutation cache
 */
DOC);

        self::assertCount(2, (new MutationContractReader())->read($node)->problems());
    }

    public function testIsMutableDescriptionRequiresTheLeadingExactToken(): void
    {
        $reader = new MutationContractReader();

        self::assertTrue($reader->isMutableDescription('+mut changed'));
        self::assertFalse($reader->isMutableDescription('changed +mut'));
        self::assertFalse($reader->isMutableDescription('+mutable'));
    }

    public function testCleanDescriptionRemovesOnlyTheMarker(): void
    {
        $reader = new MutationContractReader();

        self::assertSame('changed value', $reader->cleanDescription('+mut changed value'));
        self::assertSame('ordinary prose', $reader->cleanDescription('ordinary prose'));
    }
}
