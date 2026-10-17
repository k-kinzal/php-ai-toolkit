<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
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
        $method = new ClassMethod('run', [
            'attrGroups' => [
                new AttributeGroup([
                    new Attribute(new Name('Override')),
                ]),
            ],
        ]);

        self::assertTrue((new OverrideAttributeDetector())->has($method));
    }

    public function testHasReturnsFalseWithoutOverrideAttribute(): void
    {
        self::assertFalse((new OverrideAttributeDetector())->has(new ClassMethod('run')));
    }
}
