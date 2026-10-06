<?php

declare(strict_types=1);

namespace Guard\Init;

use Guard\Collect\Markdown\Parsing\HeadingParser;
use Guard\Policy\PolicyException;
use JsonException;
use Symfony\Component\Yaml\Yaml;

/**
 * Creates a versioned guard policy for the tools present in the target project.
 */
final class Initializer
{
    /**
     * @param ?list<string> $imports null selects presets from the project; a list imports only those names
     * @throws PolicyException when guard.yaml exists, cannot be created, or names an unknown preset
     * @throws JsonException when composer.json or composer.lock is malformed
     */
    public function write(string $path, ?array $imports = null): void
    {
        if (file_exists($path) || is_link($path)) {
            throw new PolicyException('Cannot create ' . $path . ': it already exists. Review the existing policy instead of overwriting it.');
        }
        $data = $this->configuration(dirname($path), $imports);
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
     * Builds a project policy that imports shipped presets and keeps project-specific sections inline.
     *
     * @param ?list<string> $imports null selects presets from the project; a list imports only those names
     * @return array<string, mixed>
     * @throws JsonException when composer.json or composer.lock is malformed
     * @throws PolicyException when an import name is unknown or a preset cannot be read
     */
    public function configuration(string $root, ?array $imports = null): array
    {
        $names = (new PresetSelector())->select($root, $imports);
        $document = ['version' => 1];
        $paths = (new PresetCatalog())->imports($root, $names);
        if ($paths !== []) {
            $document['imports'] = $paths;
        }
        if (in_array('metrics', $names, true)) {
            $document['metrics'] = ['source' => (new ToolDetector())->sources($root), 'exclude' => []];
        }
        $readme = $this->readme($root);
        if ($readme !== null) {
            $document['documentation'] = $readme;
        }
        $document = (new PresetOverrides())->apply($root, $names, $document);

        return (new LegacyMigration())->migrate($root, $document);
    }

    /**
     * Returns the current README headings when the project has a README.
     *
     * @return ?array<string, mixed>
     */
    public function readme(string $root): ?array
    {
        if (!is_file($root . '/README.md')) {
            return null;
        }
        $source = file_get_contents($root . '/README.md');
        $headings = [];
        foreach ((new HeadingParser())->parse($source === false ? '' : $source) as $heading) {
            $headings[] = $heading->notation();
        }

        return ['files' => ['README.md' => ['headings' => $headings]], 'scan' => ['README.md']];
    }

    /**
     * Returns the standard metric limits shipped in rules/metrics.yaml.
     *
     * @return array<string, array<string, int>>
     * @throws PolicyException when metrics.yaml does not define integer limits
     */
    public function limits(): array
    {
        return (new PresetCatalog())->limits();
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
