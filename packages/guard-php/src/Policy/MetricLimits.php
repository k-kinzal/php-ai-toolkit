<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Config\Assignment\FilePolicyAssigner;
use Guard\Execution\Context;
use Guard\Execution\Plan;
use Guard\Reporting\Finding;
use Guard\Structure\Php\SourceMetrics;
use JsonException;
use RuntimeException;

/**
 * Applies metric profiles to collected PHP metrics without accessing source files.
 */
final class MetricLimits implements Policy
{
    /**
     * Creates the MetricLimits with its declared dependencies.
     */
    public function __construct(private \Guard\Config\Value\MetricsConfig $config)
    {
    }
    /**
     * @return array<string, \Guard\Collect\Input>
     */
    public function inputs(Context $context): array
    {
        if ($context->configuration->scope !== null) {
            return ['sources' => new \Guard\Collect\Input(new \Guard\Collect\Selection('patterns', ['**/*.php']), 'php.metrics')];
        }
        return ['sources' => new \Guard\Collect\Input(new \Guard\Collect\Selection(
            'descendants',
            $this->config->scan->roots,
            $this->config->scan->exclude,
            '.php',
            false,
            'Configured scan root is not a directory: {absolute}. Set scan.roots to existing source directories.',
        ), 'php.metrics')];
    }
    /** Evaluates profile assignments against shared, already-measured source.
     * @throws RuntimeException
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function evaluate(\Guard\Collect\InputSet $inputs, Context $context): Plan
    {
        $files = [];
        $values = [];
        foreach ($inputs->get('sources')->files as $value) {
            $files[$value->file->path] = $value->file->relativePath;
            $values[$value->file->path] = $value;
        }
        ksort($files);
        $findings = [];
        foreach ((new FilePolicyAssigner())->assign($this->config, $files, $context->configuration->scope === null) as $assignment) {
            $value = $values[$assignment->path];
            if (!$value->readable) {
                continue;
            }
            $metrics = $value->value();
            if (!$metrics instanceof SourceMetrics) {
                throw new PolicyException('MetricLimits requires php.metrics to return SourceMetrics. Register MetricParser for that structure.');
            }
            $metrics = new SourceMetrics(new \Guard\Structure\Php\FileMetric\FileMetric($assignment->relativePath, $metrics->file->physicalLines, $metrics->file->nonCommentLines), $metrics->classes, $metrics->functions, true);
            foreach ((new Limit\MetricLimitInspector())->violations($metrics, $assignment->policy->limits, $assignment->policy->name) as $violation) {
                $findings[] = new Finding($violation->path, 'metrics.' . $violation->rule, 'required', (new \Guard\Reporting\RuleMessages())->diagnostic('metrics.' . $violation->rule, $violation->message));
            }
        }
        return new Plan($findings, []);
    }
}
