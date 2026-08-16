<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Git;

use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Git\GitCommandRunner;
use PhpAiToolkit\DocGen\Git\GitWorktree;
use PhpAiToolkit\DocGen\Git\TempDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(GitWorktree::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(GitCommandRunner::class)]
#[UsesClass(TempDirectory::class)]
final class GitWorktreeTest extends TestCase
{
    public function testCreateChecksTheCommitOutIntoAFreshDirectory(): void
    {
        $captured = '';
        $temp = new TempDirectory();
        $worktree = new GitWorktree(new GitCommandRunner(static function (string $command) use (&$captured): array {
            $captured = $command;

            return ['status' => 0, 'output' => ''];
        }), $temp);

        $path = $worktree->create('/home/dev/project', '2f0c1a2');

        self::assertDirectoryExists($path);
        self::assertStringContainsString(escapeshellarg('worktree'), $captured);
        self::assertStringContainsString(escapeshellarg('--detach'), $captured);
        self::assertStringContainsString(escapeshellarg($path), $captured);
        self::assertStringContainsString(escapeshellarg('2f0c1a2'), $captured);

        $temp->remove($path);
    }

    public function testCreateReportsAFailedCheckout(): void
    {
        $worktree = new GitWorktree(new GitCommandRunner(
            static fn (string $command): array => ['status' => 128, 'output' => 'fatal: invalid reference'],
        ));

        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('fatal: invalid reference');

        $worktree->create('/home/dev/project', 'nope');
    }

    public function testLinkVendorLinksTheInstalledDependenciesIntoTheCheckout(): void
    {
        $temp = new TempDirectory();
        $repository = $temp->create('docgen-repo-');
        $checkout = $temp->create('docgen-checkout-');
        mkdir($repository . '/vendor', 0700, true);
        file_put_contents($repository . '/vendor/autoload.php', '<?php');

        (new GitWorktree())->linkVendor($repository, $checkout);

        self::assertFileExists($checkout . '/vendor/autoload.php');

        $temp->remove($checkout);
        $temp->remove($repository);
    }

    public function testLinkVendorLeavesACheckoutThatAlreadyHasDependencies(): void
    {
        $temp = new TempDirectory();
        $repository = $temp->create('docgen-repo-');
        $checkout = $temp->create('docgen-checkout-');
        mkdir($repository . '/vendor', 0700, true);
        mkdir($checkout . '/vendor', 0700, true);
        file_put_contents($checkout . '/vendor/own.php', '<?php');

        (new GitWorktree())->linkVendor($repository, $checkout);

        self::assertFileExists($checkout . '/vendor/own.php');
        self::assertFalse(is_link($checkout . '/vendor'));

        $temp->remove($checkout);
        $temp->remove($repository);
    }

    public function testLinkVendorDoesNothingWithoutInstalledDependencies(): void
    {
        $temp = new TempDirectory();
        $repository = $temp->create('docgen-repo-');
        $checkout = $temp->create('docgen-checkout-');

        (new GitWorktree())->linkVendor($repository, $checkout);

        self::assertFileDoesNotExist($checkout . '/vendor');

        $temp->remove($checkout);
        $temp->remove($repository);
    }

    public function testRemoveUnlinksTheDependenciesBeforeDroppingTheCheckout(): void
    {
        $commands = [];
        $temp = new TempDirectory();
        $repository = $temp->create('docgen-repo-');
        $checkout = $temp->create('docgen-checkout-');
        mkdir($repository . '/vendor', 0700, true);
        file_put_contents($repository . '/vendor/autoload.php', '<?php');
        symlink($repository . '/vendor', $checkout . '/vendor');
        $worktree = new GitWorktree(new GitCommandRunner(static function (string $command) use (&$commands): array {
            $commands[] = $command;

            return ['status' => 0, 'output' => ''];
        }), $temp);

        $worktree->remove($repository, $checkout);

        self::assertFileExists($repository . '/vendor/autoload.php');
        self::assertDirectoryDoesNotExist($checkout);
        self::assertCount(2, $commands);
        self::assertStringContainsString(escapeshellarg('remove'), $commands[0]);
        self::assertStringContainsString(escapeshellarg('prune'), $commands[1]);

        $temp->remove($repository);
    }
}
