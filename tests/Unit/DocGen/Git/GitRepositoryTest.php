<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Git;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\DocGenException;
use Toolkit\DocGen\Git\GitCommandRunner;
use Toolkit\DocGen\Git\GitRepository;

/**
 * @covers \Toolkit\DocGen\Git\GitRepository
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Git\GitCommandRunner
 */
#[CoversClass(GitRepository::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(GitCommandRunner::class)]
final class GitRepositoryTest extends TestCase
{
    public function testRootReturnsTheWorkingTreeOfADirectory(): void
    {
        $repository = new GitRepository(new GitCommandRunner(
            static fn (string $command): array => ['status' => 0, 'output' => '/home/dev/project'],
        ));

        self::assertSame('/home/dev/project', $repository->root('/home/dev/project/src'));
    }

    public function testRootExplainsThatDiffModeNeedsAGitWorkingTree(): void
    {
        $repository = new GitRepository(new GitCommandRunner(
            static fn (string $command): array => ['status' => 128, 'output' => 'fatal: not a git repository'],
        ));

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Not a git working tree: /tmp/plain. Diff mode compares two revisions, so it needs the documented project to live in a git repository.');

        $repository->root('/tmp/plain');
    }

    public function testRootRejectsAnEmptyAnswerFromGit(): void
    {
        $repository = new GitRepository(new GitCommandRunner(
            static fn (string $command): array => ['status' => 0, 'output' => ''],
        ));

        $this->expectException(DocGenException::class);

        $repository->root('/tmp/plain');
    }

    public function testCommitResolvesARevisionToItsCommit(): void
    {
        $repository = new GitRepository(new GitCommandRunner(
            static fn (string $command): array => ['status' => 0, 'output' => '2f0c1a2b3c4d5e6f'],
        ));

        self::assertSame('2f0c1a2b3c4d5e6f', $repository->commit('/home/dev/project', 'main'));
    }

    public function testCommitNamesTheRevisionItCouldNotResolve(): void
    {
        $repository = new GitRepository(new GitCommandRunner(
            static fn (string $command): array => ['status' => 1, 'output' => ''],
        ));

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Unknown git revision: nope. Use a commit, branch, or tag that exists in /home/dev/project.');

        $repository->commit('/home/dev/project', 'nope');
    }

    public function testLabelAbbreviatesARevision(): void
    {
        $repository = new GitRepository(new GitCommandRunner(
            static fn (string $command): array => ['status' => 0, 'output' => '2f0c1a2'],
        ));

        self::assertSame('2f0c1a2', $repository->label('/home/dev/project', 'main'));
    }

    public function testLabelKeepsTheRevisionGitCannotAbbreviate(): void
    {
        $repository = new GitRepository(new GitCommandRunner(
            static fn (string $command): array => ['status' => 1, 'output' => ''],
        ));

        self::assertSame('main', $repository->label('/home/dev/project', 'main'));
    }
}
