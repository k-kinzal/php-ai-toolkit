<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Doc;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Doc\MutationContract;

/**
 * @covers \Toolkit\DocGen\Analysis\Doc\MutationContract
 */
#[CoversClass(MutationContract::class)]
final class MutationContractTest extends TestCase
{
    public function testMutatesParameterFindsAnExactName(): void
    {
        $contract = new MutationContract(['value']);

        self::assertTrue($contract->mutatesParameter('value'));
        self::assertFalse($contract->mutatesParameter('other'));
    }

    public function testMutableParametersReturnsDeclaredNames(): void
    {
        self::assertSame(['left', 'right'], (new MutationContract(['left', 'right']))->mutableParameters());
    }

    public function testMutatesThisReturnsReceiverEffect(): void
    {
        self::assertTrue((new MutationContract([], true))->mutatesThis());
    }

    public function testMutatesGlobalReturnsGlobalEffect(): void
    {
        self::assertTrue((new MutationContract([], false, true))->mutatesGlobal());
    }

    public function testProblemsReturnsSyntaxProblems(): void
    {
        self::assertSame(['broken'], (new MutationContract([], false, false, ['broken']))->problems());
    }
}
