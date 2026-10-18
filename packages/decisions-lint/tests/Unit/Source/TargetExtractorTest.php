<?php

declare(strict_types=1);

namespace Tests\Unit\Source;

use DecisionsLint\Source\Declaration;
use DecisionsLint\Source\DeclarationScanner;
use DecisionsLint\Source\Target;
use DecisionsLint\Source\TargetExtractor;
use DecisionsLint\Source\Tokens;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Source\TargetExtractor
 * @uses \DecisionsLint\Source\Declaration
 * @uses \DecisionsLint\Source\DeclarationScanner
 * @uses \DecisionsLint\Source\Target
 * @uses \DecisionsLint\Source\Tokens
 */
#[CoversClass(TargetExtractor::class)]
#[UsesClass(Declaration::class)]
#[UsesClass(DeclarationScanner::class)]
#[UsesClass(Target::class)]
#[UsesClass(Tokens::class)]
final class TargetExtractorTest extends TestCase
{
    public function testFileStandsForTheWholeFile(): void
    {
        $target = (new TargetExtractor())->file('docs/guide.md', "# Guide\n");
        self::assertSame(Target::FILE, $target->kind);
        self::assertSame('guide.md', $target->name);
        self::assertSame('docs/guide.md', $target->qualifiedName);
        self::assertSame(1, $target->line);
        self::assertSame(1, $target->column);
        self::assertSame("# Guide\n", $target->declaration);
        self::assertSame("# Guide\n", $target->context('class'));
    }

    public function testExtractGivesEveryDeclarationItsPositionNameAndSource(): void
    {
        $code = <<<'PHP'
            <?php
            namespace App;

            /**
             * Analyzes.
             */
            final class Analyzer
            {
                private int $count = 0;

                /**
                 * Analyzes input.
                 */
                public function analyze(string $input): void
                {
                }
            }

            function helper(): void {}
            PHP;
        $targets = (new TargetExtractor(new DeclarationScanner()))->extract('src/Analyzer.php', $code);
        self::assertCount(4, $targets);
        [$class, $property, $method, $function] = $targets;
        self::assertSame('App\Analyzer', $class->qualifiedName);
        self::assertSame('Analyzer', $class->class);
        self::assertSame(7, $class->line);
        self::assertSame(1, $class->column);
        self::assertSame('final class Analyzer', $class->signature);
        self::assertStringStartsWith("/**\n * Analyzes.", $class->declaration);
        self::assertSame($class->declaration, $class->enclosing);
        self::assertSame('App\Analyzer::$count', $property->qualifiedName);
        self::assertSame('private', $property->visibility);
        self::assertSame('App\Analyzer::analyze', $method->qualifiedName);
        self::assertSame(14, $method->line);
        self::assertSame(5, $method->column);
        self::assertSame('public function analyze(string $input): void', $method->signature);
        self::assertStringStartsWith("/**\n     * Analyzes input.", $method->declaration);
        self::assertSame($class->declaration, $method->enclosing);
        self::assertSame('App\helper', $function->qualifiedName);
        self::assertSame('', $function->class);
        self::assertSame($code, $function->enclosing);
        self::assertSame($code, $function->fileSource);
    }

    public function testTargetUsesTheFileAsEnclosingSourceWhenTheOwnerIsUnknown(): void
    {
        $code = "<?php\nclass A { public function f() {} }";
        $tokens = new Tokens($code);
        $declaration = new Declaration('method', 'f', '', 'Missing', 5, 5, 9, 11, ['public']);
        $target = (new TargetExtractor())->target('src/A.php', $code, $tokens, $declaration, []);
        self::assertSame('Missing::f', $target->qualifiedName);
        self::assertSame($code, $target->enclosing);
        self::assertSame('Missing', $target->class);
    }
}
