<?php

declare(strict_types=1);

namespace Guard\Cli;

use Guard\Config\ConfigurationLoader;
use Guard\Execution\Pipeline;
use Guard\Execution\Registry;
use Guard\Policy\FileChange;
use Guard\Repair\AtomicWriter;
use Guard\Reporting\Reporter;
use JsonException;
use Nette\Neon\Exception as NeonException;
use RuntimeException;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Checks a project against its policy and, when asked, repairs it.
 */
final class PolicyRun
{
    /**
     * Creates a run with the policies a registry provides, or the configured ones.
     */
    public function __construct(private ?Registry $registry = null)
    {
    }

    /**
     * Evaluates the policy file, writes the repairs a repairing run may make, and reports.
     *
     * A repairing run writes nothing while a required violation blocks it,
     * and a dry run writes nothing at all.
     *
     * @return int 1 when a required rule is violated, 0 otherwise
     * @throws RuntimeException when the policy or an input document is invalid
     * @throws JsonException when a JSON document is malformed
     * @throws NeonException when a NEON document is malformed
     */
    public function run(string $path, string $format, bool $repair, bool $dryRun, OutputInterface $output): int
    {
        $config = (new ConfigurationLoader())->load($path);
        $plan = (new Pipeline($this->registry))->run($config, $path, $repair);
        $reporter = new Reporter();
        $blocked = $reporter->hasErrors($plan->blockingFindings);
        $action = $repair ? ($dryRun ? 'would change' : 'changed') : 'checked';
        if ($repair && !$dryRun && !$blocked) {
            (new AtomicWriter())->apply($plan->changes);
        }
        if ($repair && $blocked) {
            $action = 'blocked';
        }
        $root = rtrim($config->root, '/') . '/';
        $resolved = realpath($config->root);
        $resolvedRoot = rtrim($resolved === false ? $config->root : $resolved, '/') . '/';
        $changes = $format === 'json' ? $plan->changes : array_map(static fn (FileChange $change): FileChange => new FileChange(
            str_starts_with($change->path, $root) ? substr($change->path, strlen($root))
                : (str_starts_with($change->path, $resolvedRoot) ? substr($change->path, strlen($resolvedRoot)) : $change->path),
            $change->original,
            $change->replacement,
        ), $plan->changes);
        $output->write($reporter->render($plan->findings, $changes, $format, $action, $output->isDecorated()), false, OutputInterface::OUTPUT_RAW);

        return $reporter->hasErrors($plan->findings) ? 1 : 0;
    }
}
