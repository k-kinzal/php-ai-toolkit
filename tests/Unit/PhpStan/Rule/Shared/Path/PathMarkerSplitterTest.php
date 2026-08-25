<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared\Path;

use PhpAiToolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\Shared\Path\PathMarkerSplitter
 */
#[CoversClass(PathMarkerSplitter::class)]
final class PathMarkerSplitterTest extends TestCase
{
    public function testSplitReturnsRootAndRelativePath(): void
    {
        self::assertSame(['/project', 'Domain/User.php'], (new PathMarkerSplitter())->split('/project/src/Domain/User.php', '/src/'));
    }
}
