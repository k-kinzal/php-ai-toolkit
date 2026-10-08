<?php

declare(strict_types=1);

namespace Guard\Config;

use Guard\Config\Reader\DirectoryPolicyReader;
use Guard\Config\Reader\ExtensionConfigReader;
use Guard\Config\Reader\HeadingPolicyReader;
use Guard\Config\Reader\MetricPolicyReader;
use Guard\Config\Reader\ScopeConfigReader;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\DirectoryEntries;
use Guard\Policy\FieldConstraints;
use Guard\Policy\HeadingStructure;
use Guard\Policy\MetricLimits;
use Guard\Policy\PolicyBinding;
use JsonException;

/**
 * Adapts the existing YAML schema into ordinary policy registrations.
 */
final class ConfigurationLoader
{
    /** Preserves schema validation and diagnostic order at the configuration boundary.
     * @throws PolicyException
     * @throws JsonException
     */
    public function load(string $path): Configuration
    {
        $data = (new ImportResolver())->resolve($path);
        $root = dirname($path);
        $policies = [];
        if (array_key_exists('metrics', $data)) {
            $policies[] = new PolicyBinding('metric-limits', new MetricLimits((new MetricPolicyReader())->read($data['metrics'], $root)), 0);
        }
        if (array_key_exists('structure', $data)) {
            $policies[] = new PolicyBinding('directory-entries', new DirectoryEntries((new DirectoryPolicyReader())->read($data['structure'], $root)), 10);
        }
        if (array_key_exists('documentation', $data)) {
            $policies[] = new PolicyBinding('heading-structure', new HeadingStructure((new HeadingPolicyReader())->read($data['documentation'], $root, basename($path))), 20);
        }
        $rules = (new RuleReader())->read($data['configuration'] ?? []);
        /**
         * Field failures historically take precedence, while source findings are reported first.
         */
        array_unshift($policies, new PolicyBinding('field-constraints', new FieldConstraints($rules), 30));
        $scope = array_key_exists('collect', $data) ? (new ScopeConfigReader())->read($data['collect']) : null;
        return new Configuration($root, $policies, (new ExtensionConfigReader())->read($data['extensions'] ?? []), $scope);
    }
}
