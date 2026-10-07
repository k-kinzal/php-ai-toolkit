<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Collect\Input;
use Guard\Collect\InputSet;
use Guard\Collect\Selection;
use Guard\Collect\StructuredFile;
use Guard\Document\DocumentFailure;
use Guard\Execution\Context;
use Guard\Execution\FileChange;
use Guard\Execution\Plan;
use Guard\Reporting\Finding;
use Guard\Structure\ParsedDocument;
use JsonException;
use Nette\Neon\Exception as NeonException;
use RuntimeException;

/**
 * Checks and repairs field constraints on already-structured configuration values.
 */
final class FieldConstraints implements Policy
{
    /** @var list<non-empty-list<Rule>> */
    private array $groups;
    /**
     * @param list<Rule> $rules
     */
    public function __construct(array $rules)
    {
        $groups = [];
        foreach ($rules as $rule) {
            $key = implode('/', array_filter(explode('/', str_replace('\\', '/', $rule->file)), static fn (string $part): bool => $part !== '' && $part !== '.'));
            $groups[$key][] = $rule;
        }
        $this->groups = array_values($groups);
    }
    /**
     * @return array<string, Input>
     */
    public function inputs(Context $context): array
    {
        $inputs = [];
        foreach ($this->groups as $index => $rules) {
            $rule = $rules[0];
            $inputs['file' . $index] = new Input(new Selection('files', [$rule->file], [], '', true, '', $context->configPath, 'Rule ' . $rule->id . ' targets guard.yaml itself. Policies cannot rewrite their own constraints.'), $rule->format);
        }
        return $inputs;
    }
    /** Plans each document in input order, preserving diagnostic precedence.
     * @throws RuntimeException
     * @throws JsonException
     * @throws NeonException
     */
    public function evaluate(InputSet $inputs, Context $context): Plan
    {
        $findings = [];
        $changes = [];
        foreach ($this->groups as $index => $rules) {
            $file = $inputs->get('file' . $index)->files[$rules[0]->file] ?? null;
            if ($file === null) {
                continue;
            }
            if (!$file->file->entry->file) {
                $findings = array_merge($findings, $this->missing($rules, basename($context->configPath)));
                continue;
            }
            try {
                $plan = $this->plan($file, $rules, $context->repair);
            } catch (RuntimeException|JsonException|NeonException $error) {
                throw (new DocumentFailure())->at($file->file->path, $error);
            }
            $findings = array_merge($findings, $plan->findings);
            $changes = array_merge($changes, $plan->changes);
        }
        return new Plan($findings, $changes, $findings);
    }
    /**
     * Reports every rule of a target file that does not exist, at each rule's own level.
     *
     * @param list<Rule> $rules
     * @return list<Finding>
     */
    public function missing(array $rules, string $configName): array
    {
        $findings = [];
        foreach ($rules as $rule) {
            $findings[] = new Finding($rule->file, $rule->id, $rule->level, $rule->file . ' does not exist, but this rule checks ' . $rule->select . ' in it. Create ' . $rule->file . ' with a compliant value; removing the rule requires a human to update ' . $configName . '.');
        }
        return $findings;
    }
    /** Evaluates a private document copy without filesystem access.
     * @param non-empty-list<Rule> $rules
     * @throws RuntimeException
     * @throws JsonException
     * @throws NeonException
     */
    public function plan(StructuredFile $file, array $rules, bool $repair): Plan
    {
        if (!$file->readable) {
            throw new PolicyException('Cannot read ' . $file->file->path . '. Check file permissions.');
        }
        $value = $file->value();
        if (!$value instanceof ParsedDocument) {
            throw new PolicyException('FieldConstraints requires ParsedDocument. Register DocumentStructurer for the requested format.');
        }
        $document = $value->copy();
        $original = $value->original();
        $evaluator = new RuleEvaluator();
        $changed = false;
        foreach ($rules as $rule) {
            if ($rule->format !== $rules[0]->format) {
                throw new PolicyException('Conflicting formats for ' . $rule->file . '. Use one format for all its rules.');
            }
            if ($repair && $rule->repairable && !$evaluator->accepts($rule, $document)) {
                $document->write($rule->select, $rule->repair);
                $changed = true;
            }
        }
        $findings = [];
        foreach ($rules as $rule) {
            if (!$evaluator->accepts($rule, $document)) {
                $findings[] = $evaluator->finding($rule);
            }
        }
        return new Plan($findings, $changed ? [new FileChange($file->file->path, $original, $document->encode())] : [], $findings);
    }
}
