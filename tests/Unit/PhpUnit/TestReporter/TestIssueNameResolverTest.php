<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use PhpAiToolkit\PhpUnit\TestReporter\TestIssueNameResolver;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\MetadataCollection;

#[CoversClass(TestIssueNameResolver::class)]
final class TestIssueNameResolverTest extends TestCase
{
    public function testResolveReturnsClassQualifiedMethodName(): void
    {
        $resolver = new TestIssueNameResolver();
        $test = new TestMethod(
            self::class,
            'testExample',
            '/tmp/ExampleTest.php',
            12,
            new TestDox('', '', ''),
            MetadataCollection::fromArray([]),
            TestDataCollection::fromArray([]),
        );

        self::assertSame(self::class . '::testExample', $resolver->resolve($test));
    }
}
