<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Parallel;

use PhpAiToolkit\DocGen\Parallel\ForkSupport;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Parallel\ForkSupport
 */
#[CoversClass(ForkSupport::class)]
final class ForkSupportTest extends TestCase
{
    public function testIsAvailableAgreesWithTheReasonItGives(): void
    {
        $support = new ForkSupport();

        self::assertSame($support->unavailableReason() === null, $support->isAvailable());
    }

    public function testRequiredFunctionsNamesWhatForkingNeeds(): void
    {
        $required = (new ForkSupport())->requiredFunctions();

        self::assertContains('pcntl_fork', $required);
        self::assertContains('stream_socket_pair', $required);
    }

    public function testUnavailableReasonNamesTheMissingExtensionOrTheCodeCache(): void
    {
        $reason = (new ForkSupport())->unavailableReason();

        self::assertTrue($reason === null || $reason === 'the pcntl extension is not available' || $reason === 'OPcache or JIT is enabled for the CLI');
    }

    public function testIsSharedCodeCacheEnabledReportsWhatThisRuntimeHasTurnedOn(): void
    {
        $support = new ForkSupport();

        self::assertSame($support->unavailableReason() === 'OPcache or JIT is enabled for the CLI', $support->isSharedCodeCacheEnabled() && function_exists('pcntl_fork'));
    }
}
