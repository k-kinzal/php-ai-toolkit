<?php

declare(strict_types=1);

namespace Tests\Unit\Guard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Toolkit\Guard\Config\Schema
 * @uses \Toolkit\Guard\Policy\PolicyException
 */
#[CoversClass(\Toolkit\Guard\Config\Schema::class)]
#[UsesClass(\Toolkit\Guard\Policy\PolicyException::class)]
final class SchemaTest extends TestCase
{
    public function testMappingRejectsMisspelledConstraints(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        $this->expectExceptionMessage('unknown key "levle"');
        (new \Toolkit\Guard\Config\Schema())->mapping(['levle' => 'required'], ['level'], 'rule');
    }

    public function testStringsRejectsScalars(): void
    {
        $this->expectException(\Toolkit\Guard\Policy\PolicyException::class);
        (new \Toolkit\Guard\Config\Schema())->strings('src', 'metrics.source');
    }

}
