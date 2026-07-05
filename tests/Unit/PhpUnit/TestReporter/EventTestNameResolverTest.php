<?php

declare(strict_types=1);

namespace Tests\Unit\PhpUnit\TestReporter;

use function interface_exists;

use Override;
use PhpAiToolkit\PhpUnit\TestReporter\EventTestNameResolver;
use PHPUnit\Event\Code\TestDox;
use PHPUnit\Event\Code\TestMethod;
use PHPUnit\Event\TestData\TestDataCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use PHPUnit\Metadata\MetadataCollection;

#[CoversClass(EventTestNameResolver::class)]
final class EventTestNameResolverTest extends TestCase
{
    #[Override]
    protected function setUp(): void
    {
        parent::setUp();
        if (!interface_exists('PHPUnit\Runner\Extension\Extension')) {
            self::markTestSkipped('Requires PHPUnit 10 event extension API.');
        }
    }

    public function testResolveReturnsClassQualifiedMethodName(): void
    {
        $resolver = new EventTestNameResolver();
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
