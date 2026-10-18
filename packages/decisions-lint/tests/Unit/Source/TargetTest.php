<?php

declare(strict_types=1);

namespace Tests\Unit\Source;

use DecisionsLint\Source\Target;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Source\Target
 */
#[CoversClass(Target::class)]
final class TargetTest extends TestCase
{
    public function testContextReturnsTheDeclarationTheClassOrTheFile(): void
    {
        $target = new Target('method', 'foo', 'App\Analyzer::foo', 'App', 'Analyzer', 'src/Analyzer.php', 12, 5, 'public function foo(): void', 'public', ['public'], 'DECLARATION', 'CLASS', 'FILE');
        self::assertSame('DECLARATION', $target->context('declaration'));
        self::assertSame('CLASS', $target->context('class'));
        self::assertSame('FILE', $target->context('file'));
        self::assertSame('method', $target->kind);
        self::assertSame('App\Analyzer::foo', $target->qualifiedName);
        self::assertSame(['public'], $target->modifiers);
        self::assertSame('FILE', $target->fileSource);
        self::assertSame('CLASS', $target->enclosing);
        self::assertSame('DECLARATION', $target->declaration);
    }

    public function testVariablesExposeEveryTemplatePlaceholderOfATarget(): void
    {
        $target = new Target('method', 'foo', 'App\Analyzer::foo', 'App', 'Analyzer', 'src/Analyzer.php', 12, 5, 'public function foo(): void', 'public', ['public'], 'D', 'C', 'F');
        self::assertSame([
            'file' => 'src/Analyzer.php',
            'line' => '12',
            'column' => '5',
            'kind' => 'method',
            'name' => 'foo',
            'class' => 'Analyzer',
            'namespace' => 'App',
            'qualified_name' => 'App\Analyzer::foo',
            'signature' => 'public function foo(): void',
            'visibility' => 'public',
        ], $target->variables());
    }
}
