<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\AnonymousClassDetector;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnonymousClassDetector::class)]
final class AnonymousClassDetectorTest extends TestCase
{
    public function testIsAnonymousReturnsTrueForAnonymousClassNode(): void
    {
        self::assertTrue((new AnonymousClassDetector())->isAnonymous(new \PhpParser\Node\Stmt\Class_(null), self::createStub(Scope::class)));
    }
}
