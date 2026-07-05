<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter\Legacy;

use PhpAiToolkit\PhpUnit\TestReporter\Legacy\LegacyFailureDiffResolver;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use SebastianBergmann\Comparator\ComparisonFailure;

#[CoversClass(LegacyFailureDiffResolver::class)]
final class LegacyFailureDiffResolverTest extends TestCase
{
    public function testResolveReturnsNullForFailureWithoutComparisonFailure(): void
    {
        $resolver = new LegacyFailureDiffResolver();

        self::assertNull($resolver->resolve(new AssertionFailedError('Failed')));
    }

    public function testResolveReturnsComparisonDiff(): void
    {
        $resolver = new LegacyFailureDiffResolver();
        $failure = new ExpectationFailedException('Failed', new ComparisonFailure(true, false, 'true', 'false'));

        $diff = $resolver->resolve($failure);

        self::assertStringContainsString('--- Expected', (string) $diff);
        self::assertStringContainsString('+++ Actual', (string) $diff);
    }
}
