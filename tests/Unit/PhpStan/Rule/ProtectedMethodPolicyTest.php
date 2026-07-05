<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\OverrideAttributeDetector;
use PhpAiToolkit\PhpStan\Rule\ProtectedMethodPolicy;
use PhpParser\Modifiers;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProtectedMethodPolicy::class)]
#[UsesClass(OverrideAttributeDetector::class)]
final class ProtectedMethodPolicyTest extends TestCase
{
    public function testAllowsReturnsTrueForAbstractClassProtectedMethod(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Example', ['flags' => Modifiers::ABSTRACT]);
        $method = new \PhpParser\Node\Stmt\ClassMethod('run', ['flags' => Modifiers::PROTECTED]);

        self::assertTrue((new ProtectedMethodPolicy())->allows($class, $method));
    }

    public function testAllowsReturnsTrueForOverrideProtectedMethod(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Example');
        $method = new \PhpParser\Node\Stmt\ClassMethod('run', [
            'flags' => Modifiers::PROTECTED,
            'attrGroups' => [
                new \PhpParser\Node\AttributeGroup([
                    new \PhpParser\Node\Attribute(new \PhpParser\Node\Name('Override')),
                ]),
            ],
        ]);

        self::assertTrue((new ProtectedMethodPolicy())->allows($class, $method));
    }
}
