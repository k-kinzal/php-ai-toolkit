<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc;

use Guard\Config\Loc\ConfigStringListReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\ConfigStringListReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ConfigStringListReader::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class ConfigStringListReaderTest extends TestCase
{
    public function testReadReturnsConfiguredStringList(): void
    {
        self::assertSame(['src'], (new ConfigStringListReader())->read(['paths' => ['src']], 'paths', []));
    }

    public function testReadRejectsInvalidStringList(): void
    {
        $this->expectException(PolicyException::class);

        (new ConfigStringListReader())->read(['paths' => [1]], 'paths', []);
    }

    public function testReadRequiredRejectsEmptyList(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('must contain at least one entry');

        (new ConfigStringListReader())->readRequired(['roots' => []], 'roots', 'scan', false);
    }
}
