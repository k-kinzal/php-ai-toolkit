<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\TestFailureLineResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TestFailureLineResolver::class)]
final class TestFailureLineResolverTest extends TestCase
{
    public function testResolveReturnsFirstStackFrameLineForTestFile(): void
    {
        $resolver = new TestFailureLineResolver();
        $stackTrace = "/tmp/FooTest.php:42\n/tmp/vendor/phpunit/phpunit/src/Framework/Assert.php:10";

        self::assertSame(42, $resolver->resolve($stackTrace, '/tmp/FooTest.php', 5));
    }

    public function testResolveReturnsFirstPhpTraceFrameLineForTestFile(): void
    {
        $resolver = new TestFailureLineResolver();
        $stackTrace = "#0 /tmp/FooTest.php(43): PHPUnit\\Framework\\Assert::assertSame()\n#1 /tmp/vendor/phpunit/phpunit/src/Framework/TestCase.php(1): FooTest->testFoo()";

        self::assertSame(43, $resolver->resolve($stackTrace, '/tmp/FooTest.php', 5));
    }

    public function testResolveReturnsFallbackLineWhenStackTraceDoesNotMatch(): void
    {
        $resolver = new TestFailureLineResolver();

        self::assertSame(5, $resolver->resolve('', '/tmp/FooTest.php', 5));
    }
}
