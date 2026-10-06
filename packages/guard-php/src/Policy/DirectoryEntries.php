<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Collect\Input;
use Guard\Collect\InputSet;
use Guard\Collect\Selection;
use Guard\Config\Value\StructureConfig;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Policy\Inspection\DirectoryPatternMatcher;
use Guard\Policy\Inspection\DirectoryRuleInspector;
use Guard\Reporting\Finding;

/**
 * Checks directory-entry constraints using metadata from the shared collection.
 */
final class DirectoryEntries implements Policy
{
    /**
     * Creates the DirectoryEntries with its declared dependencies.
     */
    public function __construct(private StructureConfig $config)
    {
    }
    /**
     * @return array<string, Input>
     */
    public function inputs(Context $context): array
    {
        if ($context->configuration->scope !== null) {
            return ['entries' => new Input(new Selection('directories', ['.']))];
        }
        return ['entries' => new Input(new Selection('directories', $this->config->paths, $this->config->exclude, '', false, 'Configured path is not a directory: {path}'), null)];
    }
    /**
     * Evaluates every matching constraint without walking the filesystem.
     */
    public function evaluate(InputSet $inputs, Context $context): Plan
    {
        $listings = $inputs->get('entries')->directories;
        $findings = [];
        foreach ($this->config->rules as $rule) {
            foreach ($listings as $listing) {
                if ((new DirectoryPatternMatcher())->matches($rule->path, $listing->relativePath)) {
                    foreach ((new DirectoryRuleInspector())->inspect($rule, $listing, $listings) as $violation) {
                        $findings[] = new Finding($violation->path, 'structure.' . $violation->rule, 'required', $violation->message);
                    }
                }
            }
        }
        return new Plan($findings, []);
    }
}
