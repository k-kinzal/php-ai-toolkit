<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Input\StructuredFile;
use Guard\Policy\Benchmark\BenchmarkRules;
use Guard\Policy\TestDependency\TestDependencyRules;
use Guard\Structure\Document\DataDocument;
use Guard\Structure\Document\DocumentFailure;
use Guard\Structure\ParsedDocument;
use JsonException;
use Nette\Neon\Exception as NeonException;
use RuntimeException;

/**
 * Checks and repairs field constraints on already-structured configuration values.
 */
final class FieldConstraints implements Policy
{
    /**
     * @var list<array{file: string, format: string, rules: list<Rule>}>
     */
    private array $groups;
    /**
     * @param list<Rule> $rules
     */
    public function __construct(array $rules, private ?TestDependencyRules $testDependencies = null, private bool $benchmarks = false)
    {
        $groups = [];
        foreach ($rules as $rule) {
            $key = implode('/', array_filter(explode('/', str_replace('\\', '/', $rule->file)), static fn (string $part): bool => $part !== '' && $part !== '.'));
            $groups[$key] ??= ['file' => $key, 'format' => $rule->format, 'rules' => []];
            $groups[$key]['rules'][] = $rule;
        }
        if ($this->testDependencies !== null) {
            foreach ($this->testDependencies->files() as $file => $format) {
                $groups[$file] ??= ['file' => $file, 'format' => $format, 'rules' => []];
            }
        }
        if ($this->benchmarks) {
            $groups['composer.json'] ??= ['file' => 'composer.json', 'format' => 'json', 'rules' => []];
        }
        $this->groups = array_values($groups);
    }
    /**
     * @return array<string, Input>
     */
    public function inputs(Context $context): array
    {
        $inputs = [];
        foreach ($this->groups as $index => $group) {
            $id = $group['rules'][0]->id ?? 'test-dependencies';
            $inputs['file' . $index] = new Input(new Selection('files', [$group['file']], [], '', true, '', $context->configPath, 'Rule ' . $id . ' targets guard.yaml itself. Policies cannot rewrite their own constraints.'), $group['format']);
        }
        if ($this->testDependencies !== null) {
            $inputs['test-directories'] = new Input(new Selection('directories', ['.'], ['*/*']));
        }
        if ($this->benchmarks) {
            $inputs['bench-directory'] = (new BenchmarkFiles())->directory();
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
        foreach ($this->groups as $index => $group) {
            $file = $inputs->get('file' . $index)->files[$group['file']] ?? null;
            $rules = $group['rules'];
            $directories = $this->testDependencies === null ? [] : ($inputs->get('test-directories')->directories['.']->dirNames ?? []);
            if ($file === null) {
                continue;
            }
            $generated = $this->dependencyRules($file, $directories);
            if ($this->benchmarks && $group['file'] === 'composer.json') {
                $generated = array_merge($generated, (new BenchmarkRules())->rules($inputs->get('bench-directory')->directories['bench']->fileNames ?? []));
            }
            $rules = array_merge($rules, $generated);
            if ($rules === []) {
                continue;
            }
            if (!$file->file->entry->file && $generated !== [] && $file->file->relativePath !== 'composer.json') {
                $plan = $this->create($file, $rules, $context->repair);
                $findings = array_merge($findings, $plan->findings);
                $changes = array_merge($changes, $plan->changes);
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
     * Expands the optional dependency policy from collected directory metadata.
     * @param list<string> $directories
     * @return list<Rule>
     * @throws RuntimeException
     * @throws JsonException
     * @throws NeonException
     */
    public function dependencyRules(StructuredFile $file, array $directories): array
    {
        if ($this->testDependencies === null || !isset($this->testDependencies->files()[$file->file->relativePath]) || array_intersect($directories, array_keys($this->testDependencies->directories())) === []) {
            return [];
        }
        $value = $file->file->entry->file ? $file->value() : null;
        $document = $value instanceof ParsedDocument ? $value->copy() : new DataDocument('yaml', '{}');
        if (!$document instanceof DataDocument) {
            throw new PolicyException('Test dependency rules require JSON Composer and YAML Deptrac documents. Correct the conflicting format in guard.yaml.');
        }
        return $this->testDependencies->rules($file->file->relativePath, $directories, $document);
    }

    /**
     * Plans a new Deptrac file using the same assertions as existing files.
     * @param non-empty-list<Rule> $rules
     * @throws RuntimeException
     * @throws JsonException
     * @throws NeonException
     */
    public function create(StructuredFile $file, array $rules, bool $repair): Plan
    {
        $parsed = new ParsedDocument(new DataDocument('yaml', '{}'), '{}');
        $plan = $this->plan(new StructuredFile($file->file, true, $parsed, null), $rules, $repair);
        $changes = [];
        foreach ($plan->changes as $change) {
            $changes[] = new FileChange($change->path, null, $change->replacement);
        }
        return new Plan($plan->findings, $changes, $plan->blockingFindings);
    }

    /**
     * Reports every rule of a target file that does not exist, at each rule's own level.
     * @throws JsonException
     *
     * @param list<Rule> $rules
     * @return list<Finding>
     */
    public function missing(array $rules, string $configName): array
    {
        $findings = [];
        foreach ($rules as $rule) {
            $findings[] = new Finding($rule->file, $rule->id, $rule->level, $rule->file . ' does not exist. Create it with the required configuration: ' . (new Diagnostic\FieldMessage())->render($rule, false) . ' Removing the rule requires a human to update ' . $configName . '.', false);
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
                $findings[] = $evaluator->finding($rule, $repair);
            }
        }
        return new Plan($findings, $changed ? [new FileChange($file->file->path, $original, $document->encode())] : [], $findings);
    }
}
