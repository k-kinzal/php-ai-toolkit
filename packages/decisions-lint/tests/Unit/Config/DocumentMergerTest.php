<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use DecisionsLint\Config\DocumentMerger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Config\DocumentMerger
 */
#[CoversClass(DocumentMerger::class)]
final class DocumentMergerTest extends TestCase
{
    public function testMergeDeepMergesMappingsAndReplacesListsAndScalars(): void
    {
        $base = ['severity' => 'warning', 'paths' => ['src'], 'decision' => ['type' => 'predicate', 'instructions' => 'Q?', 'threshold' => 0.5], 'violations' => [['answer' => false]]];
        $override = ['severity' => 'error', 'paths' => ['app'], 'decision' => ['threshold' => 0.9], 'violations' => [], 'enabled' => false];
        self::assertSame([
            'severity' => 'error',
            'paths' => ['app'],
            'decision' => ['type' => 'predicate', 'instructions' => 'Q?', 'threshold' => 0.9],
            'violations' => [],
            'enabled' => false,
        ], (new DocumentMerger())->merge($base, $override));
    }

    public function testIsMappingRequiresStringKeysAndEntries(): void
    {
        $merger = new DocumentMerger();
        self::assertTrue($merger->isMapping(['a' => 1]));
        self::assertFalse($merger->isMapping([1, 2]));
        self::assertFalse($merger->isMapping([]));
    }

    public function testMappingCastsKeysToStrings(): void
    {
        self::assertSame(['1' => 'a', 'b' => 'c'], (new DocumentMerger())->mapping([1 => 'a', 'b' => 'c']));
    }
}
