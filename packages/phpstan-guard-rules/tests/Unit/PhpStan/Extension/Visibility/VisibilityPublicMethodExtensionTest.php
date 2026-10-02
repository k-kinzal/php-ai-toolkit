<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\Visibility;

use PHPStan\Reflection\MethodReflection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Extension\Visibility\VisibilityPublicMethodExtension;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;

/**
 * @covers \Toolkit\PhpStan\Extension\Visibility\VisibilityPublicMethodExtension
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector
 */
#[CoversClass(VisibilityPublicMethodExtension::class)]
#[UsesClass(PublicApiVisibilityDetector::class)]
final class VisibilityPublicMethodExtensionTest extends TestCase
{
    public function testIsAlwaysUsedMarksPublicMethod(): void
    {
        $method = self::createStub(MethodReflection::class);
        $method->method('getDocComment')->willReturn('/** @visibility public */');

        self::assertTrue((new VisibilityPublicMethodExtension(new PublicApiVisibilityDetector()))->isAlwaysUsed($method));
    }
}
