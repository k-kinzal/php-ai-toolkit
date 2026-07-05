<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\PathMarkerSplitter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PathMarkerSplitter::class)]
final class PathMarkerSplitterTest extends TestCase
{
    public function testSplitReturnsRootAndRelativePath(): void
    {
        self::assertSame(['/project', 'Domain/User.php'], (new PathMarkerSplitter())->split('/project/src/Domain/User.php', '/src/'));
    }
}
