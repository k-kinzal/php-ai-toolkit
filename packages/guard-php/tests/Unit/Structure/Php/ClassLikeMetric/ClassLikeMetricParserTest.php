<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php\ClassLikeMetric;

use Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader;
use Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric;
use Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser;
use Guard\Structure\Php\Token\ClassLikeTokenMatcher;
use Guard\Structure\Php\Token\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetricParser
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeMetric
 * @uses \Guard\Structure\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Structure\Php\Token\PhpTokenNavigator
 */
#[CoversClass(ClassLikeMetricParser::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(ClassLikeMetric::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class ClassLikeMetricParserTest extends TestCase
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

        $metrics = (new ClassLikeMetricParser())->collect($tokens);

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

        $metrics = (new ClassLikeMetricParser())->collect($tokens);

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

        $metrics = (new ClassLikeMetricParser())->collect($tokens);

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

        $metrics = (new ClassLikeMetricParser())->collect($tokens);

        self::assertSame(['interface'], array_map(static fn ($metric): string => $metric->kind, $metrics));
        self::assertSame([4], array_map(static fn ($metric): int => $metric->lineCount(), $metrics));
    }
}
