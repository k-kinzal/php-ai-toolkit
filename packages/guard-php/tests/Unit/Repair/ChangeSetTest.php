<?php

declare(strict_types=1);

namespace Tests\Unit\Repair;

use Guard\Config\Configuration;
use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\DirectoryListing;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\FileChange;
use Guard\Policy\Plan;
use Guard\Policy\PolicyBinding;
use Guard\Repair\ChangeSet;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
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
#[UsesClass(DirectoryListing::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Entry::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Configuration::class)]
#[UsesClass(Context::class)]
#[UsesClass(FileChange::class)]
#[UsesClass(Plan::class)]
#[UsesClass(PolicyBinding::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Finding::class)]
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
