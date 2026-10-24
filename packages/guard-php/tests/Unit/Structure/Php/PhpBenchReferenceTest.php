<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php;

use Guard\Structure\Php\PhpBenchReference;
use Guard\Structure\Php\Tokens;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\PhpBenchReference
 * @uses \Guard\Structure\Php\Tokens
 */
#[CoversClass(PhpBenchReference::class)]
#[UsesClass(Tokens::class)]
final class PhpBenchReferenceTest extends TestCase
{
    /**
     * @dataProvider providerSources
     */
    #[DataProvider('providerSources')]
    public function testExistsRecognizesOnlyPhpNamespaceReferences(string $source, bool $expected): void
    {
        self::assertSame($expected, (new PhpBenchReference())->exists(new Tokens(array_values(PhpToken::tokenize($source)))));
    }

    /**
     * @return iterable<string, array{string, bool}>
     */
    public static function providerSources(): iterable
    {
        yield 'aliased attributes' => ['<?php namespace Bench; use PhpBench\Attributes as Bench; #[Bench\Revs(1)] class Bench {}', true];
        yield 'annotations import' => ['<?php use PhpBench\Annotations as Bench; /** @Bench\Revs(1) */ class Bench {}', true];
        yield 'fully qualified attribute' => ['<?php #[\PhpBench\Attributes\Revs(1)] class Bench {}', true];
        yield 'group import' => ['<?php use PhpBench\{Attributes, Benchmark};', true];
        yield 'nested group import' => ['<?php use PhpBench\Attributes\{Revs, Iterations};', true];
        yield 'case insensitive namespace' => ['<?php use PHPBench\Attributes as Bench;', true];
        yield 'qualified call' => ['<?php \PhpBench\foo();', true];
        yield 'custom bench' => ['<?php namespace Bench; class Bench { public function benchRun(): void { microtime(true); } }', false];
        yield 'comment' => ['<?php // use PhpBench\Attributes as Bench;', false];
        yield 'doc comment' => ['<?php /** @\PhpBench\Attributes\Revs(1) */ class Bench {}', false];
        yield 'string' => ['<?php $name = "PhpBench\\Attributes";', false];
        yield 'heredoc' => ["<?php \$text = <<<TXT\nPhpBench\\Attributes\nTXT;", false];
        yield 'namespace declaration' => ['<?php namespace PhpBench\Attributes; class Revs {}', false];
        yield 'bracketed namespace declaration' => ['<?php namespace PhpBench\Attributes { class Revs {} }', false];
        yield 'unrelated namespace' => ['<?php use Other\PhpBench\Attributes; use PhpBenchFake\Attributes;', false];
        yield 'unqualified name' => ['<?php class PhpBench {}', false];
        yield 'html' => ['PhpBench\Attributes', false];
    }
}
