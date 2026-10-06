<?php

declare(strict_types=1);

namespace Guard\Cli;

use Closure;
use Guard\Config\ConfigurationLoader;
use Guard\Execution\AtomicWriter;
use Guard\Execution\Context;
use Guard\Execution\Pipeline;
use Guard\Extension\Registry;
use Guard\Init\Initializer;
use Guard\Reporting\Reporter;
use JsonException;
use Nette\Neon\Exception as NeonException;
use RuntimeException;

/**
 * The public guard command boundary.
 */
final class Application
{
    /**
     * @param Closure(string): void $output
     */
    public function __construct(private string $directory, private Closure $output, private ?Registry $registry = null)
    {
    }
    /**
     * @param list<string> $arguments arguments without the executable name
     */
    public function run(array $arguments): int
    {
        try {
            return $this->execute((new Arguments())->parse($arguments));
        } catch (RuntimeException | JsonException | NeonException $exception) {
            ($this->output)('Guard error: ' . $exception->getMessage() . "\n");
            return 2;
        }
    }
    /**
     * @param array{command: string, config: string, format: string, dryRun: bool, imports: ?list<string>} $arguments
     * @throws JsonException
     * @throws NeonException
     */
    public function execute(array $arguments): int
    {
        if ($arguments['command'] === '--help' || $arguments['command'] === '-h') {
            ($this->output)("Usage: guard check|apply|init [--config=guard.yaml] [--format=text|json]\ninit accepts --import=NAME[,NAME]. Apply accepts --dry-run. Required violations exit 1; warnings exit 0; invalid input exits 2.\n");
            return 0;
        }
        $path = str_starts_with($arguments['config'], '/') ? $arguments['config'] : $this->directory . '/' . $arguments['config'];
        if ($arguments['command'] === 'init') {
            (new Initializer())->write($path, $arguments['imports']);
            ($this->output)('Created ' . $path . ". Review the detected recommendations, then run guard check.\n");
            return 0;
        }
        return $this->check($path, $arguments);
    }
    /**
     * @param array{command: string, config: string, format: string, dryRun: bool, imports: ?list<string>} $arguments
     * @throws JsonException
     * @throws NeonException
     */
    public function check(string $path, array $arguments): int
    {
        $config = (new ConfigurationLoader())->load($path);
        $repair = $arguments['command'] === 'apply';
        $plan = (new Pipeline($this->registry))->run(new Context($config, $path, $repair));
        $findings = $plan->findings;
        $reporter = new Reporter();
        $blocked = $reporter->hasErrors($plan->blockingFindings);
        $action = $arguments['dryRun'] ? 'would change' : 'changed';
        if ($repair && !$arguments['dryRun'] && !$blocked) {
            (new AtomicWriter())->apply($plan->changes);
        }
        if ($blocked) {
            $action = 'blocked';
        }
        ($this->output)($reporter->render($findings, $plan->changes, $arguments['format'], $action));
        return $reporter->hasErrors($findings) ? 1 : 0;
    }
}
