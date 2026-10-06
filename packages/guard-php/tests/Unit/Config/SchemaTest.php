<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Schema
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(\Guard\Config\Schema::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
final class SchemaTest extends TestCase
{
    public function testMappingRejectsMisspelledConstraints(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('unknown key "levle"');
        (new \Guard\Config\Schema())->mapping(['levle' => 'required'], ['level'], 'rule');
    }

    public function testStringsRejectsScalars(): void
    {
        $this->expectException(\Guard\Policy\PolicyException::class);
        (new \Guard\Config\Schema())->strings('src', 'metrics.source');
    }

}
