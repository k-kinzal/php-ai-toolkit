<?php

declare(strict_types=1);

namespace Guard\Policy\Benchmark;

use Guard\Policy\Rule;
use JsonException;

/**
 * Derives Composer commands from the benchmark files in the collected directory.
 */
final class BenchmarkRules
{
    /**
     * @param list<string> $files
     * @return non-empty-list<Rule>
     * @throws JsonException
     */
    public function rules(array $files): array
    {
        $rules = [
            $this->equal('autoload', '/autoload-dev/psr-4/Bench\\', 'bench/'),
            $this->equal('script', '/scripts/bench', 'phpbench run bench --bootstrap=vendor/autoload.php'),
        ];
        if (count($files) < 2) {
            return $rules;
        }
        $commands = array_count_values(array_map(fn (string $file): string => $this->command($file), $files));
        foreach ($files as $file) {
            if (!(new BenchmarkLayout())->validName($file, count($files))) {
                continue;
            }
            $command = $this->command($file);
            if ($commands[$command] > 1) {
                continue;
            }
            $rules[] = $this->equal('script.' . $command, '/scripts/' . $command, 'phpbench run bench/' . $file . ' --bootstrap=vendor/autoload.php');
        }
        return $rules;
    }

    /**
     * Converts HttpClientBench.php to bench:http-client and XMLBench.php to bench:xml.
     */
    public function command(string $file): string
    {
        $stem = substr($file, 0, -strlen('Bench.php'));
        $stem = preg_replace('/([A-Z]+)([A-Z][a-z])/', '$1-$2', $stem) ?? $stem;
        return 'bench:' . strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $stem) ?? $stem);
    }

    /**
     * Builds a deterministic repair that participates in the shared Composer edit.
     * @throws JsonException
     */
    public function equal(string $id, string $select, string $value): Rule
    {
        return new Rule(
            'phpbench.' . $id,
            'composer.json',
            'json',
            $select,
            'required',
            ['equals' => $value],
            $value,
            true,
            'PHPBench configuration {select} in {file} must {expectation}. {fix}'
        );
    }
}
