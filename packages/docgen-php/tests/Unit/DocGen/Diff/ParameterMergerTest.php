<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Diff;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Diff\DiffIndex;
use Toolkit\DocGen\Diff\DiffKey;
use Toolkit\DocGen\Diff\DiffStatus;
use Toolkit\DocGen\Diff\LcsMatcher;
use Toolkit\DocGen\Diff\ParameterMerger;
use Toolkit\DocGen\Diff\SymbolFingerprint;
use Toolkit\DocGen\Model\Symbol\ParameterDoc;
use Toolkit\DocGen\Model\Symbol\TypeSignature;

/**
 * @covers \Toolkit\DocGen\Diff\ParameterMerger
 * @uses \Toolkit\DocGen\Diff\DiffIndex
 * @uses \Toolkit\DocGen\Diff\DiffKey
 * @uses \Toolkit\DocGen\Diff\DiffStatus
 * @uses \Toolkit\DocGen\Diff\LcsMatcher
 * @uses \Toolkit\DocGen\Model\Symbol\ParameterDoc
 * @uses \Toolkit\DocGen\Diff\SymbolFingerprint
 * @uses \Toolkit\DocGen\Model\Symbol\TypeSignature
 */
#[CoversClass(ParameterMerger::class)]
#[UsesClass(DiffIndex::class)]
#[UsesClass(DiffKey::class)]
#[UsesClass(DiffStatus::class)]
#[UsesClass(LcsMatcher::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(SymbolFingerprint::class)]
#[UsesClass(TypeSignature::class)]
final class ParameterMergerTest extends TestCase
{
    public function testMergeMarksAnAddedParameterWithoutTouchingTheOthers(): void
    {
        $count = new ParameterDoc('count', new TypeSignature('int', null), false, false, null, null, '');
        $label = new ParameterDoc('label', new TypeSignature('string', null), false, false, null, null, '');
        $index = new DiffIndex('main', 'HEAD');

        $merged = (new ParameterMerger())->merge([$count], [$count, $label], 'm:demo\engine::method.run', $index);

        self::assertSame([$count, $label], $merged);
        self::assertSame(DiffStatus::SAME, $index->status($index->keys()->parameter('m:demo\engine::method.run', 'count')));
        self::assertSame(DiffStatus::ADDED, $index->status($index->keys()->parameter('m:demo\engine::method.run', 'label')));
    }

    public function testMergeKeepsAParameterTheHeadDroppedAndMarksItRemoved(): void
    {
        $count = new ParameterDoc('count', new TypeSignature('int', null), false, false, null, null, '');
        $label = new ParameterDoc('label', new TypeSignature('string', null), false, false, null, null, '');
        $index = new DiffIndex('main', 'HEAD');

        $merged = (new ParameterMerger())->merge([$count, $label], [$count], 'm:demo\engine::method.run', $index);

        self::assertSame([$count, $label], $merged);
        self::assertSame(DiffStatus::REMOVED, $index->status($index->keys()->parameter('m:demo\engine::method.run', 'label')));
    }

    public function testMergeMarksAParameterWhoseTypeChanged(): void
    {
        $before = new ParameterDoc('count', new TypeSignature('int', null), false, false, null, null, '');
        $after = new ParameterDoc('count', new TypeSignature('?int', null), false, false, null, null, '');
        $index = new DiffIndex('main', 'HEAD');

        (new ParameterMerger())->merge([$before], [$after], 'm:demo\engine::method.run', $index);

        self::assertSame(DiffStatus::MODIFIED, $index->status($index->keys()->parameter('m:demo\engine::method.run', 'count')));
    }

    public function testStatusOfReadsOneMatchedPosition(): void
    {
        $merger = new ParameterMerger();
        $before = [new ParameterDoc('count', new TypeSignature('int', null), false, false, null, null, '')];
        $after = [new ParameterDoc('count', new TypeSignature('int', null), false, false, '1', null, '')];

        self::assertSame(DiffStatus::ADDED, $merger->statusOf($before, $after, ['base' => null, 'head' => 0]));
        self::assertSame(DiffStatus::REMOVED, $merger->statusOf($before, $after, ['base' => 0, 'head' => null]));
        self::assertSame(DiffStatus::MODIFIED, $merger->statusOf($before, $after, ['base' => 0, 'head' => 0]));
        self::assertSame(DiffStatus::SAME, $merger->statusOf($before, $before, ['base' => 0, 'head' => 0]));
    }

    public function testNamesListsTheParametersInDeclarationOrder(): void
    {
        $parameters = [
            new ParameterDoc('count', new TypeSignature('int', null), false, false, null, null, ''),
            new ParameterDoc('label', new TypeSignature('string', null), false, false, null, null, ''),
        ];

        self::assertSame(['count', 'label'], (new ParameterMerger())->names($parameters));
        self::assertSame([], (new ParameterMerger())->names([]));
    }
}
