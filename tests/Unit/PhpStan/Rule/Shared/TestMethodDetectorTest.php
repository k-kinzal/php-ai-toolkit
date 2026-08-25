<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\TestMethodDetector;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\TestMethodDetector
 */
#[CoversClass(TestMethodDetector::class)]
final class TestMethodDetectorTest extends TestCase
{
    public function testIsTestMethodReturnsTrueForTestPrefix(): void
    {
        self::assertTrue((new TestMethodDetector())->isTestMethod(new \PhpParser\Node\Stmt\ClassMethod('testExample')));
    }
}
