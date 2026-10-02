<?php

declare(strict_types=1);

namespace Toolkit\Guard\Init;

use JsonException;
use Symfony\Component\Yaml\Yaml;
use Toolkit\DocGuard\Markdown\HeadingParser;
use Toolkit\Guard\Policy\PolicyException;

/**
 * Creates a versioned guard policy for the tools present in the target project.
 */
final class Initializer
{
    /**
     * @throws PolicyException when guard.yaml exists or cannot be created
     * @throws JsonException
     */
    public function write(string $path): void
    {
        if (file_exists($path) || is_link($path)) {
            throw new PolicyException('Cannot create ' . $path . ': it already exists. Review the existing policy instead of overwriting it.');
        }
        $data = $this->configuration(dirname($path));
        $source = "# Project policy. Required rules fail; recommendations warn.\n" . Yaml::dump($data, 12, 2, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE);
        $handle = @fopen($path, 'x');
        if ($handle === false) {
            throw new PolicyException('Cannot create ' . $path . '. It must not exist and its directory must be writable.');
        }
        try {
            if (fwrite($handle, $source) !== strlen($source)) {
                throw new PolicyException('Could not finish writing ' . $path . '. Check disk space.');
            }
        } finally {
            fclose($handle);
        }
    }
    /**
     * @return array<string, mixed>
     * @throws JsonException
     */
    public function configuration(string $root): array
    {
        $sources = (new ToolDetector())->sources($root);
        $defaults = ['version' => 1, 'scope' => ['source' => $sources, 'exclude' => []]];
        if ($sources !== []) {
            $defaults['quality'] = ['profiles' => ['standard' => ['limits' => $this->limits()]], 'default' => 'standard', 'assignments' => []];
            $defaults['structure'] = $this->structure($sources, is_dir($root . '/tests/Unit'));
        }
        if (is_file($root . '/README.md')) {
            $source = file_get_contents($root . '/README.md');
            $headings = [];
            foreach ((new HeadingParser())->parse($source === false ? '' : $source) as $heading) {
                $headings[] = $heading->notation();
            }
            $defaults['documentation'] = ['files' => ['README.md' => ['headings' => $headings]], 'scan' => ['README.md']];
        }
        $defaults = (new LegacyMigration())->migrate($root, $defaults);
        $defaults['configuration'] = (new Recommendations())->rules($root);
        return $defaults;
    }
    /**
     * @return array<string, array<string, int>>
     */
    public function limits(): array
    {
        return [
            'file' => ['lines' => 500, 'ncloc' => 350], 'class' => ['lines' => 400],
            'trait' => ['lines' => 300], 'interface' => ['lines' => 200], 'enum' => ['lines' => 200],
            'function' => ['lines' => 50, 'cyclomatic_complexity' => 20],
            'method' => ['lines' => 50, 'cyclomatic_complexity' => 20],
        ];
    }
    /**
     * @param list<string> $sources
     * @return array<string, mixed>
     */
    public function structure(array $sources, bool $hasUnitTests = false): array
    {
        $directories = [['path' => '**', 'deny_dirs' => ['scripts', 'Scripts']]];
        foreach ($sources as $source) {
            $directories[] = ['path' => $source, 'allow' => [], 'max_dirs' => 20];
            $directories[] = ['path' => $source . '/**', 'forbid_empty' => true, 'allow' => ['*.php'],
                'deny' => ['*Helper.php', '*Helpers.php', '*Manager.php', '*Util.php', '*Utils.php', '*Service.php', '*Evidence.php', '*Evidences.php', '*Outcome.php', '*Outcomes.php', '*Probe.php', '*Probes.php'],
                'file_case' => 'pascal', 'dir_case' => 'pascal', 'max_files' => 15, 'max_dirs' => 20];
        }
        if ($hasUnitTests) {
            $directories[] = ['path' => 'tests/Unit', 'allow' => []];
            $directories[] = ['path' => 'tests/Unit/**', 'forbid_empty' => true, 'allow' => ['*Test.php'],
                'file_case' => 'pascal', 'dir_case' => 'pascal', 'max_files' => 15, 'max_dirs' => 20];
        }
        $directories[] = ['path' => 'skills/*', 'require' => ['SKILL.md'], 'forbid_empty' => true];
        return ['paths' => ['.'], 'exclude' => ['.git', '.idea', '.claude', '.agents', '.codex', '.cursor', '.gemini', '.entire', '.entrie', '.phpunit.cache', 'build', 'projects', 'vendor'], 'directories' => $directories];
    }
}
