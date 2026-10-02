<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\Visibility;

use PHPStan\Reflection\ClassMemberReflection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Extension\Visibility\VisibilityPublicConstantExtension;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;

/**
 * @covers \Toolkit\PhpStan\Extension\Visibility\VisibilityPublicConstantExtension
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector
 */
#[CoversClass(VisibilityPublicConstantExtension::class)]
#[UsesClass(PublicApiVisibilityDetector::class)]
final class VisibilityPublicConstantExtensionTest extends TestCase
{
    public function testIsAlwaysUsedMarksPublicConstant(): void
    {
        $constant = self::createStub(ClassMemberReflection::class);
        $constant->method('getDocComment')->willReturn('/** @visibility public */');

        self::assertTrue((new VisibilityPublicConstantExtension(new PublicApiVisibilityDetector()))->isAlwaysUsed($constant));
    }
}
