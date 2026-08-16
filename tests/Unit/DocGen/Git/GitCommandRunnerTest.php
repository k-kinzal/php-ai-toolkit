<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Git;

use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Git\GitCommandRunner;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitCommandRunner::class)]
#[UsesClass(DocGenException::class)]
final class GitCommandRunnerTest extends TestCase
{
    public function testCommandEscapesTheWorkingDirectoryAndEveryArgument(): void
    {
        self::assertSame(
            'git -C ' . escapeshellarg('/tmp/re po') . ' ' . escapeshellarg('rev-parse') . ' ' . escapeshellarg('--show-toplevel'),
            (new GitCommandRunner())->command(['rev-parse', '--show-toplevel'], '/tmp/re po'),
        );
    }

    public function testExecuteReportsTheStatusAndOutputOfTheLauncher(): void
    {
        $captured = '';
        $runner = new GitCommandRunner(static function (string $command) use (&$captured): array {
            $captured = $command;

            return ['status' => 3, 'output' => 'fatal: not a git repository'];
        });

        self::assertSame(['status' => 3, 'output' => 'fatal: not a git repository'], $runner->execute(['status'], '/tmp/repo'));
        self::assertSame($runner->command(['status'], '/tmp/repo'), $captured);
    }

    public function testRunReturnsTheOutputOfASuccessfulCommand(): void
    {
        $runner = new GitCommandRunner(static fn (string $command): array => ['status' => 0, 'output' => '/tmp/repo']);

        self::assertSame('/tmp/repo', $runner->run(['rev-parse', '--show-toplevel'], '/tmp/repo'));
    }

    public function testRunReportsTheFailedCommandWithItsOutput(): void
    {
        $runner = new GitCommandRunner(static fn (string $command): array => ['status' => 128, 'output' => 'fatal: bad revision']);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('git worktree add failed in /tmp/repo: fatal: bad revision');

        $runner->run(['worktree', 'add'], '/tmp/repo');
    }

    public function testRunNamesTheFailureEvenWithoutAnyOutput(): void
    {
        $runner = new GitCommandRunner(static fn (string $command): array => ['status' => 1, 'output' => '']);

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('git status failed in /tmp/repo: no output');

        $runner->run(['status'], '/tmp/repo');
    }
}
