<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc;

use Guard\Config\Loc\ConfigKeyValidator;
use Guard\Config\Loc\ConfigScalarReader;
use Guard\Config\Loc\LimitConfigReader;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\LimitConfigReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Loc\ConfigKeyValidator
 * @uses \Guard\Config\Loc\ConfigScalarReader
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(LimitConfigReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Loc\LimitConfig::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class LimitConfigReaderTest extends TestCase
{
    public function testReadReturnsPartialNestedLimits(): void
    {
        $limits = (new LimitConfigReader())->read([
            'file' => ['lines' => 10],
            'method' => ['cyclomatic_complexity' => null],
        ]);

        self::assertSame([
            'file.lines' => 10,
            'method.cyclomatic_complexity' => null,
        ], $limits);
    }

    public function testReadRejectsNonMapping(): void
    {
        $this->expectException(PolicyException::class);

        (new LimitConfigReader())->read('strict');
    }

    public function testReadRejectsUnknownMetric(): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('unsupported key "branches"');

        (new LimitConfigReader())->read(['method' => ['branches' => 10]]);
    }
}
