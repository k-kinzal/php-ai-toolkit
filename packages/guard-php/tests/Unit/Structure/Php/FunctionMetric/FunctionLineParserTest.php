<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php\FunctionMetric;

use Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader;
use Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary;
use Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader;
use Guard\Structure\Php\FunctionMetric\FunctionBodyLocator;
use Guard\Structure\Php\FunctionMetric\FunctionLineParser;
use Guard\Structure\Php\FunctionMetric\FunctionMetric;
use Guard\Structure\Php\FunctionMetric\FunctionNameReader;
use Guard\Structure\Php\FunctionMetric\FunctionScanState;
use Guard\Structure\Php\Token\ClassLikeTokenMatcher;
use Guard\Structure\Php\Token\PhpTokenNavigator;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\FunctionMetric\FunctionLineParser
 * @uses \Guard\Structure\Php\ClassLikeMetric\ClassLikeDeclarationReader
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowExpressionBoundary
 * @uses \Guard\Structure\Php\FunctionMetric\ArrowFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\BlockFunctionMetricReader
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionBodyLocator
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionMetric
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionNameReader
 * @uses \Guard\Structure\Php\FunctionMetric\FunctionScanState
 * @uses \Guard\Structure\Php\Token\ClassLikeTokenMatcher
 * @uses \Guard\Structure\Php\Token\PhpTokenNavigator
 */
#[CoversClass(FunctionLineParser::class)]
#[UsesClass(ClassLikeDeclarationReader::class)]
#[UsesClass(ArrowExpressionBoundary::class)]
#[UsesClass(ArrowFunctionMetricReader::class)]
#[UsesClass(BlockFunctionMetricReader::class)]
#[UsesClass(FunctionBodyLocator::class)]
#[UsesClass(FunctionMetric::class)]
#[UsesClass(FunctionNameReader::class)]
#[UsesClass(FunctionScanState::class)]
#[UsesClass(ClassLikeTokenMatcher::class)]
#[UsesClass(PhpTokenNavigator::class)]
final class FunctionLineParserTest extends TestCase
{
    public function testCollectReturnsFunctionMethodAndArrowFunctionLineMetrics(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

function run(): void
{
}

final class Example
{
    public function handle(): void
    {
    }
}

$mapper = fn (int $value): int => $value;
PHP, TOKEN_PARSE));

        $metrics = (new FunctionLineParser())->collect($tokens);

        self::assertSame(['function', 'method', 'function'], array_map(static fn ($metric): string => $metric->kind, $metrics));
        self::assertSame(['run', 'Example::handle', '{closure}'], array_map(static fn ($metric): string => $metric->name, $metrics));
    }
}
