<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Config;

use PhpAiToolkit\ScopeGuard\Config\ConfigScalarReader;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\ScopeGuard\Config\ConfigScalarReader
 * @uses \PhpAiToolkit\ScopeGuard\ScopeGuardException
 */
#[CoversClass(ConfigScalarReader::class)]
#[UsesClass(ScopeGuardException::class)]
final class ConfigScalarReaderTest extends TestCase
{
    /**
     * @throws ScopeGuardException
     */
    public function testStringReadsThePresentValue(): void
    {
        self::assertSame('json', (new ConfigScalarReader())->string(['reporter' => 'json'], 'reporter', 'ai', 'report'));
    }

    /**
     * @throws ScopeGuardException
     */
    public function testStringFallsBackToTheDefault(): void
    {
        self::assertSame('ai', (new ConfigScalarReader())->string([], 'reporter', 'ai', 'report'));
    }

    /**
     * @throws ScopeGuardException
     */
    public function testStringRejectsANonString(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ConfigScalarReader())->string(['reporter' => 7], 'reporter', 'ai', 'report');
    }

    /**
     * @throws ScopeGuardException
     */
    public function testStringRejectsAnEmptyValue(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ConfigScalarReader())->string(['reporter' => ''], 'reporter', 'ai', 'report');
    }

    public function testLabelQualifiesTheKeyWithItsContext(): void
    {
        self::assertSame('report.reporter', (new ConfigScalarReader())->label('report', 'reporter'));
    }

    public function testLabelReturnsThePlainKeyAtTopLevel(): void
    {
        self::assertSame('paths', (new ConfigScalarReader())->label('', 'paths'));
    }
}
