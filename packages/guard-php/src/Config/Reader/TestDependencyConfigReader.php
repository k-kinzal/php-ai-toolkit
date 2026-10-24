<?php

declare(strict_types=1);

namespace Guard\Config\Reader;

use Guard\Config\Schema;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\TestDependency\TestDependencyRules;

/**
 * Validates the test-directory dependency policy.
 */
final class TestDependencyConfigReader
{
    /**
     * Reads the Deptrac filename and the production layer names.
     * @throws PolicyException when the settings cannot describe independent test layers
     */
    public function read(mixed $value): TestDependencyRules
    {
        $schema = new Schema();
        $data = $schema->mapping($value, ['deptrac', 'source_layers'], 'test_dependencies');
        $file = $schema->string($data['deptrac'] ?? 'deptrac.yaml', 'test_dependencies.deptrac');
        $layers = $schema->strings($data['source_layers'] ?? ['Src'], 'test_dependencies.source_layers');
        if (basename($file) !== $file || !in_array(pathinfo($file, PATHINFO_EXTENSION), ['yaml', 'yml'], true)) {
            throw new PolicyException('test_dependencies.deptrac must name a YAML file in the project root. Use deptrac.yaml or deptrac.yml.');
        }
        if ($layers === [] || count(array_unique($layers)) !== count($layers)) {
            throw new PolicyException('test_dependencies.source_layers must contain unique production layer names, such as [Src].');
        }
        foreach ($layers as $layer) {
            if (in_array($layer, ['Bench', 'Fuzz', 'Spec', 'Tests'], true) || str_starts_with($layer, '+')) {
                throw new PolicyException('test_dependencies.source_layers cannot contain "' . $layer . '". List only production layers; test layers and transitive permissions are forbidden.');
            }
        }
        return new TestDependencyRules($file, $layers);
    }
}
