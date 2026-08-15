<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render\Page;

use PhpAiToolkit\DocGen\Render\Page\SymbolRow;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SymbolRow::class)]
final class SymbolRowTest extends TestCase
{
    public function testStoresKindNameFqcnPageSummaryLayersAndNamespace(): void
    {
        $row = new SymbolRow('class', 'Engine', 'Demo\Core\Engine', 'demo/pkg/Demo/Core/class.Engine.html', 'Engine summary.', ['Domain'], 'Demo\Core');

        self::assertSame('class', $row->kind);
        self::assertSame('Engine', $row->name);
        self::assertSame('Demo\Core\Engine', $row->fqcn);
        self::assertSame('demo/pkg/Demo/Core/class.Engine.html', $row->page);
        self::assertSame('Engine summary.', $row->summary);
        self::assertSame(['Domain'], $row->layers);
        self::assertSame('Demo\Core', $row->namespace);
    }

    public function testStoresUnlayeredFunctionRowWithEmptySummaryAndGlobalNamespace(): void
    {
        $row = new SymbolRow('function', 'make', 'Demo\make', 'demo/pkg/function.make.html', '', []);

        self::assertSame('function', $row->kind);
        self::assertSame('', $row->summary);
        self::assertSame([], $row->layers);
        self::assertSame('', $row->namespace);
    }
}
