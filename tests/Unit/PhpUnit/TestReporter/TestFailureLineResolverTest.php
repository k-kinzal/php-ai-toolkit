<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\TestFailureLineResolver;
use PHPUnit\Event\Code\Throwable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestFailureLineResolver::class)]
final class TestFailureLineResolverTest extends TestCase
{
    public function testResolveReturnsFirstStackFrameLineForTestFile(): void
    {
        $resolver = new TestFailureLineResolver();
        $throwable = new Throwable(
            'Exception',
            'Failed',
            'Failed',
            "/tmp/FooTest.php:42\n/tmp/vendor/phpunit/phpunit/src/Framework/Assert.php:10",
            null,
        );

        self::assertSame(42, $resolver->resolve($throwable, '/tmp/FooTest.php', 5));
    }

    public function testResolveReturnsFallbackLineWhenStackTraceDoesNotMatch(): void
    {
        $resolver = new TestFailureLineResolver();
        $throwable = new Throwable('Exception', 'Failed', 'Failed', '', null);

        self::assertSame(5, $resolver->resolve($throwable, '/tmp/FooTest.php', 5));
    }
}
