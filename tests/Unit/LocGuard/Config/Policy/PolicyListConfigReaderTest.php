<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config\Policy;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\Config\ConfigScalarReader;
use Toolkit\LocGuard\Config\LimitConfig;
use Toolkit\LocGuard\Config\LimitConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyConfig;
use Toolkit\LocGuard\Config\Policy\PolicyConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyDefinition;
use Toolkit\LocGuard\Config\Policy\PolicyListConfigReader;
use Toolkit\LocGuard\Config\Policy\PolicyResolver;

#[CoversClass(PolicyListConfigReader::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(LimitConfig::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(PolicyConfig::class)]
#[UsesClass(PolicyConfigReader::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(PolicyResolver::class)]
final class PolicyListConfigReaderTest extends TestCase
{
    public function testReadReturnsResolvedNamedPolicies(): void
    {
        $policies = (new PolicyListConfigReader())->read([
            'standard' => ['limits' => ['file' => ['lines' => 500]]],
            'native' => [
                'extends' => 'standard',
                'limits' => ['file' => ['lines' => 900]],
            ],
        ]);

        self::assertSame(500, $policies['standard']->limits->maxFileLines);
        self::assertSame(900, $policies['native']->limits->maxFileLines);
    }
}
