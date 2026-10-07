<?php

declare(strict_types=1);

namespace Tests\Support;

use Composer\InstalledVersions;
use Guard\Cli\Application;
use RuntimeException;

/**
 * Executes the CLI characterization scenarios captured before the refactor.
 */
final class BehaviorCases
{
    /**
     * @return iterable<string, array{array{name: string, files: array<array-key, string>, commands: list<list<string>>}, list<array{exit: int, output: string, files: array<array-key, string>}>}>
     */
    public function scenarios(): iterable
    {
        $base = dirname(__DIR__) . '/fixtures/Behavior/';
        $source = file_get_contents($base . 'scenarios.json');
        $expectedSource = file_get_contents($base . 'expected.json');
        if ($source === false || $expectedSource === false) {
            throw new RuntimeException('Missing guard characterization fixtures.');
        }
        /** @var list<array{name: string, files: array<array-key, string>, commands: list<list<string>>}> $scenarios */
        $scenarios = json_decode($source, true, 512, JSON_THROW_ON_ERROR);
        /** @var array<string, list<array{exit: int, output: string, files: array<array-key, string>}>> $expected */
        $expected = json_decode($expectedSource, true, 512, JSON_THROW_ON_ERROR);
        $yamlVersion = InstalledVersions::getVersion('symfony/yaml');
        $yamlMajor = $yamlVersion === null ? '' : explode('.', $yamlVersion)[0];
        $yamlMinor = $yamlVersion === null ? '' : implode('.', array_slice(explode('.', $yamlVersion), 0, 2));
        $variant = $base . 'expected-yaml' . $yamlMinor . '.json';
        if (!is_file($variant)) {
            $variant = $base . 'expected-yaml' . $yamlMajor . '.json';
        }
        if (is_file($variant)) {
            $variantSource = file_get_contents($variant);
            if ($variantSource === false) {
                throw new RuntimeException('Cannot read YAML-version characterization fixture.');
            }
            /** @var array<string, list<array{exit: int, output: string, files: array<array-key, string>}>> $overrides */
            $overrides = json_decode($variantSource, true, 512, JSON_THROW_ON_ERROR);
            $expected = array_replace($expected, $overrides);
        }
        foreach ($scenarios as $scenario) {
            yield $scenario['name'] => [$scenario, $expected[$scenario['name']]];
        }
    }
    /**
     * @param array{name: string, files: array<array-key, string>, commands: list<list<string>>} $scenario
     * @return list<array{exit: int, output: string, files: array<array-key, string>}>
     */
    public function run(array $scenario): array
    {
        $package = dirname(__DIR__);
        $project = new Project($scenario['files']);
        $results = [];
        try {
            foreach ($scenario['commands'] as $command) {
                $output = '';
                $application = new Application($project->root, static function (string $text) use (&$output): void {
                    $output .= $text;
                });
                $exit = $application->run($command);
                $results[] = ['exit' => $exit, 'output' => str_replace([$project->root, $package], ['<project>', '<package>'], $output), 'files' => $project->files()];
            }
        } finally {
            $project->remove();
        }
        return $results;
    }
}
