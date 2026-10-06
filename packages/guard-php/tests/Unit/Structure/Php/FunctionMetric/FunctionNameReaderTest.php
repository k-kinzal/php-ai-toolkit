<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php\FunctionMetric;

use Guard\Structure\Php\FunctionMetric\FunctionNameReader;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\FunctionMetric\FunctionNameReader
 */
#[CoversClass(FunctionNameReader::class)]
final class FunctionNameReaderTest extends TestCase
{
    public function testNameReturnsNamedFunctionName(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php function run(): void {}', TOKEN_PARSE));

        self::assertSame('run', (new FunctionNameReader())->name($tokens, 1));
    }

    public function testNameReturnsClosureMarkerForAnonymousFunction(): void
    {
        $tokens = array_values(PhpToken::tokenize('<?php $run = function (): void {};', TOKEN_PARSE));

        self::assertSame('{closure}', (new FunctionNameReader())->name($tokens, 5));
    }
}
