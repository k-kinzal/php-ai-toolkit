<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\PathMarkerSplitter;
use PhpAiToolkit\PhpStan\Rule\SourceUnitTestFileResolver;
use PhpAiToolkit\PhpStan\Rule\SrcUnitTestRelativePathMapper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourceUnitTestFileResolver::class)]
#[UsesClass(PathMarkerSplitter::class)]
#[UsesClass(SrcUnitTestRelativePathMapper::class)]
final class SourceUnitTestFileResolverTest extends TestCase
{
    public function testResolveReturnsExpectedUnitTestFile(): void
    {
        self::assertSame(
            '/project/tests/Unit/Domain/UserTest.php',
            (new SourceUnitTestFileResolver())->resolve('/project/src/Domain/User.php'),
        );
    }
}
