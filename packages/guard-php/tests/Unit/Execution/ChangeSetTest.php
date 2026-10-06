<?php

declare(strict_types=1);

namespace Tests\Unit\Execution;

use Guard\Execution\ChangeSet;
use Guard\Execution\FileChange;
use Guard\Policy\PolicyException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Execution\ChangeSet
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(ChangeSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
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
