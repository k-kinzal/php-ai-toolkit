<?php

declare(strict_types=1);

namespace Tests\Rector;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

final class RectorFixtureRunner
{
    /**
     * @return array{expected: string, actual: string, exitCode: int, output: string, idempotent: bool}
     */
    public function run(string $rule, string $fixture, string $set = ''): array
    {
        $root = dirname(__DIR__, 2);
        $contents = file_get_contents(__DIR__ . '/fixtures/' . $fixture . '.php.inc');
        if ($contents === false) {
            throw new RuntimeException('Cannot read Rector fixture ' . $fixture);
        }
        $parts = explode("\n-----\n", $contents, 2);
        $directory = sys_get_temp_dir() . '/toolkit-rector-' . bin2hex(random_bytes(8));
        mkdir($directory);
        $file = $directory . '/example.php';
        file_put_contents($file, $parts[0]);
        $command = [
            PHP_BINARY, $root . '/vendor/bin/rector', 'process', $file,
            '--config', __DIR__ . '/config.php', '--no-progress-bar', '--no-diffs', '--clear-cache',
        ];
        $environment = [
            'TOOLKIT_RECTOR_RULE' => $rule,
            'TOOLKIT_RECTOR_SET' => $set,
            'TOOLKIT_RECTOR_CACHE' => $directory . '/cache',
            'XDEBUG_MODE' => 'off',
        ];
        $process = new Process($command, $root, $environment);
        $process->setTimeout(60);
        try {
            $exitCode = $process->run();
            $output = $process->getOutput() . $process->getErrorOutput();
            $actual = file_get_contents($file);
            $again = new Process([...$command, '--dry-run'], $root, $environment);
            $again->setTimeout(60);
            $idempotent = $again->run() === 0;

            return [
                'expected' => $parts[1] ?? $parts[0],
                'actual' => $actual === false ? '' : $actual,
                'exitCode' => $exitCode,
                'output' => $output . $again->getOutput() . $again->getErrorOutput(),
                'idempotent' => $idempotent,
            ];
        } finally {
            (new Filesystem())->remove($directory);
        }
    }
}
