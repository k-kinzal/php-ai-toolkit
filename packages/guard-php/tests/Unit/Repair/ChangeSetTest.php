<?php

declare(strict_types=1);

namespace Tests\Unit\Repair;

use Guard\Diagnostic\PolicyException;
use Guard\Policy\FileChange;
use Guard\Repair\ChangeSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Repair\ChangeSet
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\FileChange
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Policy\PolicyBinding
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Diagnostic\Finding
 */
#[CoversClass(ChangeSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Input\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Diagnostic\Finding::class)]
final class ChangeSetTest extends TestCase
{
    public function testAddRejectsConflictingReplacementsBeforeCommit(): void
    {
        $changes = new ChangeSet();
        $changes->add(new FileChange('app.json', 'before', 'one'));
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('app.json');
        $changes->add(new FileChange('app.json', 'before', 'two'));
    }

    public function testAddRejectsInconsistentOriginalSnapshots(): void
    {
        $changes = new ChangeSet();
        $changes->add(new FileChange('app.json', 'original', 'after'));
        $this->expectException(PolicyException::class);
        $changes->add(new FileChange('app.json', 'different', 'after'));
    }

    public function testChangesCoalescesIdenticalRepairsAndKeepsFileOrder(): void
    {
        $changes = new ChangeSet();
        $first = new FileChange('b.json', 'before', 'after');
        $second = new FileChange('a.json', 'before', 'after');
        $changes->add($first);
        $changes->add($second);
        $changes->add($first);
        self::assertSame([$first, $second], $changes->changes());
    }
}
