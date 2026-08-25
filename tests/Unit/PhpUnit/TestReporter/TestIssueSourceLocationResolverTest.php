<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpUnit\TestReporter\StackTraceFrameLocationParser;
use Toolkit\PhpUnit\TestReporter\TestIssueSourceLocationResolver;

/**
 * @covers \Toolkit\PhpUnit\TestReporter\TestIssueSourceLocationResolver
 * @uses \Toolkit\PhpUnit\TestReporter\StackTraceFrameLocationParser
 */
#[CoversClass(TestIssueSourceLocationResolver::class)]
#[UsesClass(StackTraceFrameLocationParser::class)]
final class TestIssueSourceLocationResolverTest extends TestCase
{
    public function testResolveReturnsFirstApplicationFrame(): void
    {
        $resolver = new TestIssueSourceLocationResolver();

        self::assertSame([
            'file' => '/project/src/Service.php',
            'line' => 45,
        ], $resolver->resolve(
            "/project/tests/ServiceTest.php:12\n/project/vendor/phpunit/phpunit/TestCase.php:20\n/project/src/Service.php:45",
            '/project/tests/ServiceTest.php',
        ));
    }

    public function testResolveReturnsNullWhenOnlyTestAndVendorFramesExist(): void
    {
        $resolver = new TestIssueSourceLocationResolver();

        self::assertNull($resolver->resolve(
            "/project/tests/ServiceTest.php:12\n/project/vendor/phpunit/phpunit/TestCase.php:20",
            '/project/tests/ServiceTest.php',
        ));
    }

    public function testResolveReturnsFirstApplicationFrameFromPhpTrace(): void
    {
        $resolver = new TestIssueSourceLocationResolver();

        self::assertSame([
            'file' => '/project/src/Service.php',
            'line' => 45,
        ], $resolver->resolve(
            "#0 /project/tests/ServiceTest.php(12): ServiceTest->testIt()\n#1 /project/vendor/phpunit/phpunit/TestCase.php(20): PHPUnit\\Framework\\TestCase->run()\n#2 /project/src/Service.php(45): App\\Service->run()",
            '/project/tests/ServiceTest.php',
        ));
    }
}
