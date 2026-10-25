<?php

declare(strict_types=1);

namespace Tests\Integration;

use Guard\Cli\Application;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

/**
 * @coversNothing
 * @medium
 */
#[CoversNothing]
#[Medium]
final class GenericPoliciesTest extends TestCase
{
    public function testMarkdownPresetResolvesTargetsRelativeToDocumentsAndRespectsScope(): void
    {
        $root = sys_get_temp_dir() . '/guard-generic-' . uniqid('', true);
        mkdir($root . '/docs', 0777, true);
        file_put_contents($root . '/docs/README.md', '[present](../file.txt) [absent](missing.md) [excluded](../vendor/missing.md) [directory](../docs/)');
        file_put_contents($root . '/file.txt', 'present');
        file_put_contents($root . '/guard.yaml', Yaml::dump([
            'version' => 1,
            'imports' => [dirname(__DIR__, 2) . '/rules/markdown-links.yaml'],
            'collect' => ['include' => ['**'], 'exclude' => ['vendor']],
        ]));
        $output = '';
        $app = new Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        try {
            self::assertSame(1, $app->run(['check']));
            self::assertStringContainsString('docs/missing.md', $output);
            self::assertStringNotContainsString('vendor/missing.md', $output);
            file_put_contents($root . '/docs/missing.md', '# Restored');
            self::assertSame(0, $app->run(['check']));
        } finally {
            (new Filesystem())->remove($root);
        }
    }

    public function testGeneratedArtifactPresetChecksTheIndexWithoutDeletingLocalFiles(): void
    {
        $root = sys_get_temp_dir() . '/guard-generic-' . uniqid('', true);
        mkdir($root . '/build/coverage', 0777, true);
        file_put_contents($root . '/build/coverage/index.html', 'generated');
        file_put_contents($root . '/guard.yaml', Yaml::dump(['version' => 1, 'imports' => [dirname(__DIR__, 2) . '/rules/generated-artifacts.yaml']]));
        $output = '';
        $app = new Application($root, static function (string $text) use (&$output): void {
            $output .= $text;
        });
        try {
            (new Process(['git', '-C', $root, 'init', '--quiet']))->mustRun();
            self::assertSame(0, $app->run(['check']));
            (new Process(['git', '-C', $root, 'add', '--', 'build/coverage/index.html']))->mustRun();
            self::assertSame(1, $app->run(['fix']));
            self::assertStringContainsString('git.tracked_generated_file', $output);
            self::assertFileExists($root . '/build/coverage/index.html');
            self::assertSame("build/coverage/index.html\0", (new Process(['git', '-C', $root, 'ls-files', '-z']))->mustRun()->getOutput());
            (new Process(['git', '-C', $root, 'rm', '--cached', '--', 'build/coverage/index.html']))->mustRun();
            self::assertSame(0, $app->run(['check']));
        } finally {
            (new Filesystem())->remove($root);
        }
    }
}
