<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc\Policy;

use Guard\Config\Loc\ConfigKeyValidator;
use Guard\Config\Loc\ConfigScalarReader;
use Guard\Config\Loc\LimitConfigReader;
use Guard\Config\Loc\Policy\PolicyConfigReader;
use Guard\Config\Loc\Policy\PolicyDefinition;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\Policy\PolicyConfigReader
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Config\Loc\ConfigKeyValidator
 * @uses \Guard\Config\Loc\ConfigScalarReader
 * @uses \Guard\Config\Loc\LimitConfig
 * @uses \Guard\Config\Loc\LimitConfigReader
 * @uses \Guard\Config\Loc\Policy\PolicyConfig
 * @uses \Guard\Config\Loc\Policy\PolicyDefinition
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(PolicyConfigReader::class)]
#[UsesClass(\Guard\Config\Configuration::class)]
#[UsesClass(ConfigKeyValidator::class)]
#[UsesClass(ConfigScalarReader::class)]
#[UsesClass(\Guard\Config\Loc\LimitConfig::class)]
#[UsesClass(LimitConfigReader::class)]
#[UsesClass(\Guard\Config\Loc\Policy\PolicyConfig::class)]
#[UsesClass(PolicyDefinition::class)]
#[UsesClass(\Guard\Execution\Context::class)]
#[UsesClass(\Guard\Execution\FileChange::class)]
#[UsesClass(\Guard\Execution\Plan::class)]
#[UsesClass(\Guard\Policy\PolicyException::class)]
#[UsesClass(\Guard\Policy\Rule::class)]
#[UsesClass(\Guard\Reporting\Finding::class)]
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
