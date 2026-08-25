<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\Visibility;

use PHPStan\Reflection\PropertyReflection;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Extension\Visibility\VisibilityPublicPropertyExtension;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;

/**
 * @covers \Toolkit\PhpStan\Extension\Visibility\VisibilityPublicPropertyExtension
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector
 */
#[CoversClass(VisibilityPublicPropertyExtension::class)]
#[UsesClass(PublicApiVisibilityDetector::class)]
final class VisibilityPublicPropertyExtensionTest extends TestCase
{
    public function testIsAlwaysReadMarksPublicProperty(): void
    {
        $property = self::createStub(PropertyReflection::class);
        $property->method('getDocComment')->willReturn('/** @visibility public */');
        $extension = new VisibilityPublicPropertyExtension(new PublicApiVisibilityDetector());

        self::assertTrue($extension->isAlwaysRead($property, 'state'));
    }

    public function testIsAlwaysWrittenMarksPublicProperty(): void
    {
        $property = self::createStub(PropertyReflection::class);
        $property->method('getDocComment')->willReturn('/** @visibility public */');

        self::assertTrue((new VisibilityPublicPropertyExtension(new PublicApiVisibilityDetector()))->isAlwaysWritten($property, 'state'));
    }

    public function testIsInitializedMarksPublicProperty(): void
    {
        $property = self::createStub(PropertyReflection::class);
        $property->method('getDocComment')->willReturn('/** @visibility public */');

        self::assertTrue((new VisibilityPublicPropertyExtension(new PublicApiVisibilityDetector()))->isInitialized($property, 'state'));
    }
}
