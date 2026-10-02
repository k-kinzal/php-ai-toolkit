<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Small;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\OverrideAttributeDetector;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\OverrideAttributeDetector
 */
#[CoversClass(OverrideAttributeDetector::class)]
#[Small]
final class OverrideAttributeDetectorTest extends TestCase
{
    public function testHasReturnsTrueForOverrideAttribute(): void
    {
        $method = new \PhpParser\Node\Stmt\ClassMethod('run', [
            'attrGroups' => [
                new \PhpParser\Node\AttributeGroup([
                    new \PhpParser\Node\Attribute(new \PhpParser\Node\Name('Override')),
                ]),
            ],
        ]);

        self::assertTrue((new OverrideAttributeDetector())->has($method));
    }

    public function testHasReturnsFalseWithoutOverrideAttribute(): void
    {
        self::assertFalse((new OverrideAttributeDetector())->has(new \PhpParser\Node\Stmt\ClassMethod('run')));
    }
}
