<?php

declare(strict_types=1);

namespace Guard\Policy\Benchmark;

use Guard\Diagnostic\Finding;
use Guard\Input\DirectoryListing;

/**
 * Enforces a flat benchmark directory and filenames determined by its file count.
 */
final class BenchmarkLayout
{
    /**
     * A single file has the fixed name; several files each need a descriptive prefix.
     */
    public function validName(string $name, int $count): bool
    {
        return $count === 1 ? $name === 'Bench.php' : preg_match('/^[A-Z][A-Za-z0-9]*Bench\.php$/D', $name) === 1;
    }

    /**
     * @return list<Finding>
     */
    public function findings(DirectoryListing $listing): array
    {
        $findings = [];
        $count = count($listing->fileNames);
        if ($count === 0) {
            $findings[] = new Finding('bench', 'phpbench.empty', 'required', 'bench/ contains no benchmark files. Add bench/Bench.php with a PHPBench benchmark.');
        }
        foreach ($listing->dirNames as $name) {
            $findings[] = new Finding('bench/' . $name, 'phpbench.directory', 'required', 'bench/' . $name . ' is a subdirectory. Move benchmark PHP files directly into bench/ and move other contents outside bench/; only PHP files are allowed.');
        }
        foreach ($listing->fileNames as $name) {
            if (!str_ends_with($name, '.php')) {
                $findings[] = new Finding('bench/' . $name, 'phpbench.extension', 'required', 'bench/' . $name . ' is not a .php file. Move it outside bench/; only PHP benchmark files are allowed.');
            } elseif (!$this->validName($name, $count)) {
                $expected = $count === 1 ? 'Bench.php' : 'XxxBench.php with a non-empty PascalCase prefix, for example ParserBench.php';
                $findings[] = new Finding('bench/' . $name, 'phpbench.filename', 'required', 'bench/' . $name . ' has the wrong name for bench/ with ' . $count . ' file(s). Rename the file and its class to ' . $expected . ' and use namespace Bench.');
            }
        }
        return array_merge($findings, $this->collisions($listing->fileNames));
    }

    /**
     * Reports filenames that would overwrite each other's Composer command.
     * @param list<string> $files
     * @return list<Finding>
     */
    public function collisions(array $files): array
    {
        $commands = [];
        $findings = [];
        foreach ($files as $file) {
            if (!$this->validName($file, count($files))) {
                continue;
            }
            $command = (new BenchmarkRules())->command($file);
            if (isset($commands[$command])) {
                $findings[] = new Finding(
                    'bench/' . $file,
                    'phpbench.command_collision',
                    'required',
                    'bench/' . $file . ' and bench/' . $commands[$command] . ' both map to composer ' . $command . '. Rename one file and its class so each benchmark has a distinct kebab-case command.'
                );
            }
            $commands[$command] = $file;
        }
        return $findings;
    }
}
