<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\LocGuard\Config\ConfigKeyValidator;
use Toolkit\LocGuard\LocGuardException;

/**
 * @covers \Toolkit\LocGuard\Config\ConfigKeyValidator
 */
#[CoversClass(ConfigKeyValidator::class)]
final class ConfigKeyValidatorTest extends TestCase
{
    public function testRejectUnknownAcceptsKnownKeysAndRejectsTypos(): void
    {
        $validator = new ConfigKeyValidator();
        $validator->rejectUnknown(['roots' => ['src']], ['roots'], 'scan');
        self::addToAssertionCount(1);

        $this->expectException(LocGuardException::class);
        $this->expectExceptionMessage('unsupported key "root"');
        $validator->rejectUnknown(['root' => ['src']], ['roots'], 'scan');
    }
}
