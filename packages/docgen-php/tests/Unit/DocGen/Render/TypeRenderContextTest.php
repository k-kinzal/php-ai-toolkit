<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Render\TypeRenderContext;

/**
 * @covers \Toolkit\DocGen\Render\TypeRenderContext
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 */
#[CoversClass(TypeRenderContext::class)]
#[UsesClass(SymbolTable::class)]
final class TypeRenderContextTest extends TestCase
{
    public function testStoresContextData(): void
    {
        $table = new SymbolTable();
        $context = new TypeRenderContext(
            'demo/pkg/Demo/class.Widget.html',
            'Demo',
            ['w' => 'Demo\Widget'],
            ['T'],
            ['Shape' => '#alias.Shape'],
            $table,
        );

        self::assertSame('demo/pkg/Demo/class.Widget.html', $context->pagePath);
        self::assertSame('Demo', $context->namespace);
        self::assertSame(['w' => 'Demo\Widget'], $context->useMap);
        self::assertSame(['T'], $context->templates);
        self::assertSame(['Shape' => '#alias.Shape'], $context->aliases);
        self::assertSame($table, $context->symbolTable);
    }
}
