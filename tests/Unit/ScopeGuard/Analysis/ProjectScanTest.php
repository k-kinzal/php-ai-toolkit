<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis;

use PhpAiToolkit\ScopeGuard\Analysis\Declaration\DeclarationIndex;
use PhpAiToolkit\ScopeGuard\Analysis\ProjectScan;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ProjectScan::class)]
#[UsesClass(DeclarationIndex::class)]
final class ProjectScanTest extends TestCase
{
    public function testIndexIsReadable(): void
    {
        $index = new DeclarationIndex();

        self::assertSame($index, (new ProjectScan($index, [], 3))->index);
    }

    public function testReferencesAreReadable(): void
    {
        self::assertSame([], (new ProjectScan(new DeclarationIndex(), [], 3))->references);
    }

    public function testFileCountIsReadable(): void
    {
        self::assertSame(3, (new ProjectScan(new DeclarationIndex(), [], 3))->fileCount);
    }

}
