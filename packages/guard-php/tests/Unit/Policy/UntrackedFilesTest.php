<?php

declare(strict_types=1);

namespace Tests\Unit\Policy;

use Guard\Diagnostic\Finding;
use Guard\Input\Entry;
use Guard\Input\FileRecord;
use Guard\Input\FileSet;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Context;
use Guard\Policy\Plan;
use Guard\Policy\UntrackedFiles;
use Guard\Structure\Markdown\Link\LinkList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\UntrackedFiles
 * @uses \Guard\Input\Entry
 * @uses \Guard\Input\FileRecord
 * @uses \Guard\Input\FileSet
 * @uses \Guard\Input\Input
 * @uses \Guard\Input\InputSet
 * @uses \Guard\Input\Selection
 * @uses \Guard\Input\StructuredFile
 * @uses \Guard\Policy\Context
 * @uses \Guard\Policy\Plan
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Structure\Markdown\Link\LinkList
 */
#[CoversClass(UntrackedFiles::class)]
#[UsesClass(Entry::class)]
#[UsesClass(FileRecord::class)]
#[UsesClass(FileSet::class)]
#[UsesClass(Input::class)]
#[UsesClass(InputSet::class)]
#[UsesClass(Selection::class)]
#[UsesClass(StructuredFile::class)]
#[UsesClass(Context::class)]
#[UsesClass(Plan::class)]
#[UsesClass(Finding::class)]
#[UsesClass(LinkList::class)]
final class UntrackedFilesTest extends TestCase
{
    public function testInputsUsesGitIndexAndExplicitPatterns(): void
    {
        $inputs = (new UntrackedFiles(['build/**'], ['build/keep.txt']))->inputs(new Context('/project', '/project/guard.yaml', false));
        self::assertSame('git-tracked', $inputs['tracked']->selection->mode);
        self::assertSame(['build/**'], $inputs['tracked']->selection->paths);
        self::assertSame(['build/keep.txt'], $inputs['tracked']->selection->exclude);
    }

    public function testEvaluateReportsDeletedTrackedFilesWithoutRepairingGit(): void
    {
        $deleted = new FileRecord('/project/build/result', 'build/result', new Entry(false, false, false, '/project/build/result'));
        $plan = (new UntrackedFiles(['build/**']))->evaluate(new InputSet(['tracked' => new FileSet([new StructuredFile($deleted, true, null, null)], [], null)]), new Context('/project', '/project/guard.yaml', true));
        self::assertCount(1, $plan->findings);
        self::assertSame('git.tracked_generated_file', $plan->findings[0]->rule);
        self::assertSame('build/result', $plan->findings[0]->path);
        self::assertStringContainsString('git rm --cached', $plan->findings[0]->message);
        self::assertSame([], $plan->changes);
    }
}
