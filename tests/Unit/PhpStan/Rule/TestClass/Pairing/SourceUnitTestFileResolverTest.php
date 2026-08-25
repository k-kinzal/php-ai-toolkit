<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass\Pairing;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter;
use Toolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper;
use Toolkit\PhpStan\Rule\TestClass\Pairing\SourceUnitTestFileResolver;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\Pairing\SourceUnitTestFileResolver
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\SrcUnitTestRelativePathMapper
 */
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
