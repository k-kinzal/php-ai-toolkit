<?php

declare(strict_types=1);

namespace Guard\Policy\TestDependency;

use Guard\Policy\Rule;
use Guard\Structure\Document\DataDocument;
use JsonException;

/**
 * Builds field constraints from the test directories present in the collected project.
 */
final class TestDependencyRules
{
    /**
     * @param list<string> $sourceLayers
     */
    public function __construct(private string $deptrac, private array $sourceLayers)
    {
        if (!in_array('Src', $this->sourceLayers, true)) {
            $this->sourceLayers[] = 'Src';
        }
    }

    /**
     * @return array<string, string> target paths and their formats
     */
    public function files(): array
    {
        return ['composer.json' => 'json', $this->deptrac => 'yaml'];
    }

    /**
     * @return array<string, string> directory names and their namespace/layer names
     */
    public function directories(): array
    {
        return ['bench' => 'Bench', 'fuzz' => 'Fuzz', 'spec' => 'Spec', 'tests' => 'Tests'];
    }

    /**
     * Builds constraints without reading files outside the collected inputs.
     * @param list<string> $directories
     * @return list<Rule>
     * @throws JsonException
     */
    public function rules(string $file, array $directories, DataDocument $document): array
    {
        $active = array_intersect_key($this->directories(), array_fill_keys($directories, true));
        if ($active === []) {
            return [];
        }
        if ($file === 'composer.json') {
            $rules = [];
            foreach ($active as $directory => $namespace) {
                $rules[] = $this->equal($file, 'autoload.' . $directory, '/autoload-dev/psr-4/' . $namespace . '\\', $directory . '/', 'Directory ' . $directory . '/ must autoload as ' . $namespace . '\\ through autoload-dev.');
            }
            $rules[] = $this->equal($file, 'deptrac.script', '/scripts/deptrac', 'deptrac analyse --config-file=' . $this->deptrac, 'Composer must run the Deptrac configuration that enforces the test boundaries.');
            return $rules;
        }
        return (new DeptracRules())->rules($this, $file, $active, $this->sourceLayers, $document);
    }

    /**
     * Creates a repairable field constraint with a specific diagnostic.
     * @throws JsonException
     */
    public function equal(string $file, string $id, string $selector, mixed $value, string $message): Rule
    {
        return new Rule('test-dependencies.' . $id, $file, $file === 'composer.json' ? 'json' : 'yaml', $selector, 'required', ['equals' => $value], $value, true, $message . ' {select} in {file} must {expectation}. {fix}');
    }
}
