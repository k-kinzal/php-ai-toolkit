<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Cli;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Cli\LocGuardCliValueOption;
use Toolkit\LocGuard\Cli\LocGuardCliValueOptionParser;

/**
 * @covers \Toolkit\LocGuard\Cli\LocGuardCliValueOptionParser
 */
#[CoversClass(LocGuardCliValueOptionParser::class)]
#[UsesClass(LocGuardCliValueOption::class)]
final class LocGuardCliValueOptionParserTest extends TestCase
{
    public function testParseSupportsSeparateAndEqualsOptionValues(): void
    {
        $parser = new LocGuardCliValueOptionParser();
        $separate = $parser->parse(['--explain', 'src/Example.php'], 0);
        $equals = $parser->parse(['--format=json'], 0);

        self::assertInstanceOf(LocGuardCliValueOption::class, $separate);
        self::assertInstanceOf(LocGuardCliValueOption::class, $equals);
        self::assertSame('explain', $separate->key);
        self::assertTrue($separate->consumesNext);
        self::assertSame('reporter', $equals->key);
        self::assertSame('json', $equals->value);
    }
}
