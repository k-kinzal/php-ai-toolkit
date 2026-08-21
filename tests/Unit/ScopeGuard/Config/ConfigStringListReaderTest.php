<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Config;

use PhpAiToolkit\ScopeGuard\Config\ConfigStringListReader;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConfigStringListReader::class)]
#[UsesClass(ScopeGuardException::class)]
final class ConfigStringListReaderTest extends TestCase
{
    /**
     * @throws ScopeGuardException
     */
    public function testReadReturnsThePresentList(): void
    {
        self::assertSame(['src'], (new ConfigStringListReader())->read(['paths' => ['src']], 'paths', [], ''));
    }

    /**
     * @throws ScopeGuardException
     */
    public function testReadFallsBackToTheDefault(): void
    {
        self::assertSame(['src'], (new ConfigStringListReader())->read([], 'paths', ['src'], ''));
    }

    /**
     * @throws ScopeGuardException
     */
    public function testReadRejectsANonList(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ConfigStringListReader())->read(['paths' => 'src'], 'paths', [], '');
    }

    /**
     * @throws ScopeGuardException
     */
    public function testReadRejectsAnEmptyEntry(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new ConfigStringListReader())->read(['paths' => ['']], 'paths', [], '');
    }

    public function testLabelQualifiesTheKeyWithItsContext(): void
    {
        self::assertSame('report.order_by', (new ConfigStringListReader())->label('report', 'order_by'));
    }

    public function testLabelReturnsThePlainKeyAtTopLevel(): void
    {
        self::assertSame('paths', (new ConfigStringListReader())->label('', 'paths'));
    }
}
