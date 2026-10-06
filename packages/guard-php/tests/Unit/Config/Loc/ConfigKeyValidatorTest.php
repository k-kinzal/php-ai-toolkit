<?php

declare(strict_types=1);

namespace Tests\Unit\Config\Loc;

use Guard\Config\Loc\ConfigKeyValidator;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\Loc\ConfigKeyValidator
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ConfigKeyValidator::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class ConfigKeyValidatorTest extends TestCase
{
    public function testRejectUnknownAcceptsKnownKeysAndRejectsTypos(): void
    {
        $validator = new ConfigKeyValidator();
        $validator->rejectUnknown(['roots' => ['src']], ['roots'], 'scan');
        self::addToAssertionCount(1);

        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('unsupported key "root"');
        $validator->rejectUnknown(['root' => ['src']], ['roots'], 'scan');
    }
}
