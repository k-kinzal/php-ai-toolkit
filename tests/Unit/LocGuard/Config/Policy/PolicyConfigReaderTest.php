<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\LimitConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyDefinition;

#[CoversClass(PolicyConfigReader::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(PolicyDefinition::class)]
final class PolicyConfigReaderTest extends TestCase
{
    public function testReadReturnsUnresolvedPolicyDefinition(): void
    {
        $definition = (new PolicyConfigReader())->read('native', [
            'extends' => 'standard',
            'limits' => ['file' => ['lines' => 900]],
        ]);

        self::assertSame('native', $definition->name);
        self::assertSame('standard', $definition->extends);
        self::assertSame(['file.lines' => 900], $definition->limitPatch);
    }
}
