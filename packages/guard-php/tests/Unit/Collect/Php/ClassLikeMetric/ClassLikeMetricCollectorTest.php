<?php

declare(strict_types=1);

namespace Tests\Unit\Collect\Php\ClassLikeMetric;

use Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader;
use Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric;
use Guard\Collect\Php\ClassLikeMetric\ClassLikeMetricCollector;
use Guard\Collect\Php\Token\ClassLikeTokenMatcher;
use Guard\Collect\Php\Token\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetricCollector
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Collect\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Collect\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Collect\Php\Token\PhpTokenNavigator
 */
#[CoversClass(ClassLikeMetricCollector::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(ClassLikeMetric::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class ClassLikeMetricCollectorTest extends TestCase
{
    public function testCollectReturnsClassLikeMetrics(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

final class Example
{
}

trait SharedBehavior
{
}

interface ExampleContract
{
}

enum Status
{
    case Open;
}
PHP));

        $metrics = (new ClassLikeMetricCollector())->collect($tokens);

        self::assertSame(['class', 'trait', 'interface', 'enum'], array_map(static fn ($metric): string => $metric->kind, $metrics));
        self::assertSame(['Example', 'SharedBehavior', 'ExampleContract', 'Status'], array_map(static fn ($metric): string => $metric->name, $metrics));
    }

    public function testCollectHandlesAttributesAnonymousClassesAndStaticClassConstants(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

$name = Example::class;
#[Attribute]
final class Example
{
}
$anonymous = new class () {
};
PHP, TOKEN_PARSE));

        $metrics = (new ClassLikeMetricCollector())->collect($tokens);

        self::assertSame(['Example', 'anonymous@8'], array_map(static fn ($metric): string => $metric->name, $metrics));
        self::assertSame([3, 2], array_map(static fn ($metric): int => $metric->lineCount(), $metrics));
    }

    public function testCollectHandlesAnonymousClassWithoutArguments(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

$anonymous = new class {
};
PHP, TOKEN_PARSE));

        $metrics = (new ClassLikeMetricCollector())->collect($tokens);

        self::assertSame(['anonymous@3'], array_map(static fn ($metric): string => $metric->name, $metrics));
        self::assertSame([2], array_map(static fn ($metric): int => $metric->lineCount(), $metrics));
    }

    public function testCollectHandlesInterfaceMethodsWithoutBodies(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

interface Contract
{
    public function run(): void;
}
PHP, TOKEN_PARSE));

        $metrics = (new ClassLikeMetricCollector())->collect($tokens);

        self::assertSame(['interface'], array_map(static fn ($metric): string => $metric->kind, $metrics));
        self::assertSame([4], array_map(static fn ($metric): int => $metric->lineCount(), $metrics));
    }
}
