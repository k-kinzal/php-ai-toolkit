<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\ClassDesign;

use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\ClassDesign\ProtectedMethodPolicy;
use Toolkit\PhpStan\Rule\Shared\OverrideAttributeDetector;

/**
 * @covers \Toolkit\PhpStan\Rule\ClassDesign\ProtectedMethodPolicy
 * @uses \Toolkit\PhpStan\Rule\Shared\OverrideAttributeDetector
 */
#[CoversClass(ProtectedMethodPolicy::class)]
#[UsesClass(OverrideAttributeDetector::class)]
final class ProtectedMethodPolicyTest extends TestCase
{
    public function testAllowsReturnsTrueForAbstractClassProtectedMethod(): void
    {
        $class = new Class_('Example', ['flags' => Class_::MODIFIER_ABSTRACT]);
        $method = new \PhpParser\Node\Stmt\ClassMethod('run', ['flags' => Class_::MODIFIER_PROTECTED]);

        self::assertTrue((new ProtectedMethodPolicy())->allows($class, $method));
    }

    public function testAllowsReturnsTrueForOverrideProtectedMethod(): void
    {
        $class = new Class_('Example');
        $method = new \PhpParser\Node\Stmt\ClassMethod('run', [
            'flags' => Class_::MODIFIER_PROTECTED,
            'attrGroups' => [
                new \PhpParser\Node\AttributeGroup([
                    new \PhpParser\Node\Attribute(new \PhpParser\Node\Name('Override')),
                ]),
            ],
        ]);

        self::assertTrue((new ProtectedMethodPolicy())->allows($class, $method));
    }
}
