<?php

declare(strict_types=1);

namespace Guard\Cli;

use Guard\Config\ConfigurationLoader;
use Guard\Diagnostic\PolicyException;
use Guard\Execution\Pipeline;
use Guard\Execution\Registry;
use Guard\Reporting\Baseline;
use Guard\Reporting\Filtering\FindingFilter;
use Guard\Reporting\Filtering\ReportScope;
use Guard\Reporting\Reporter;
use JsonException;
use Nette\Neon\Exception as NeonException;
use RuntimeException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Checks every policy, consumes the baseline, and applies display-only diagnostic filters.
 */
final class CheckRun
{
    /**
     * Allows callers to supply additional policies.
     */
    public function __construct(private ?Registry $registry = null)
    {
    }

    /**
     * @throws RuntimeException
     * @throws JsonException
     * @throws NeonException
     */
    public function run(string $path, string $format, InputInterface $input, OutputInterface $output): int
    {
        $query = $input->getOption('query');
        if ($query !== null && (!is_string($query) || trim($query) === '')) {
            throw new PolicyException('Provide non-empty search text for --query, such as --query=metrics.');
        }
        $level = $input->getOption('level');
        $filter = new FindingFilter($query ?? '', is_string($level) ? $level : null, $input->getOption('fixable') === true);
        $baseline = $this->baselinePath($path, $input);
        $file = new BaselineFile();
        $json = $baseline === null ? null : $file->read($baseline);
        if ($json !== null) {
            $file->validate($json, $baseline);
        }
        $configuration = (new ConfigurationLoader())->load($path);
        $plan = (new Pipeline($this->registry))->run($configuration, $path, false);
        $match = $json === null ? null : (new Baseline())->filter($plan->findings, $json);
        $findings = $match === null ? $plan->findings : $match->findings;
        $visible = $filter->select($findings);
        $scope = $baseline === null && count($visible) === count($findings) ? null : new ReportScope($findings, $baseline, $match === null ? 0 : $match->suppressed, $match === null ? 0 : $match->unmatched);
        $reporter = new Reporter();
        $output->write($reporter->render($visible, [], $format, 'checked', $output->isDecorated(), $scope), false, OutputInterface::OUTPUT_RAW);
        return $reporter->hasErrors($findings) ? 1 : 0;
    }

    /**
     * Uses the conventional file if present, but never ignores an explicitly missing baseline.
     * @throws PolicyException
     */
    public function baselinePath(string $config, InputInterface $input): ?string
    {
        $requested = $input->getOption('baseline');
        if ($input->getOption('no-baseline') === true) {
            if ($requested !== null) {
                throw new PolicyException('Use either --baseline or --no-baseline, not both.');
            }
            return null;
        }
        $path = (new BaselineFile())->path($config, is_string($requested) ? $requested : null);
        return $requested !== null || file_exists($path) || is_link($path) ? $path : null;
    }
}
