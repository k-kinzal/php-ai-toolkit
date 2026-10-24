<?php

declare(strict_types=1);

namespace Guard\Policy;

use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Input\Input;
use Guard\Input\InputSet;
use Guard\Input\Selection;
use Guard\Policy\Benchmark\BenchmarkLayout;
use Guard\Structure\Php\PhpBenchReference;
use Guard\Structure\Php\Tokens;
use JsonException;
use Nette\Neon\Exception;
use RuntimeException;

/**
 * Checks benchmark layout and PHPBench references without executing benchmark code.
 */
final class BenchmarkFiles implements Policy
{
    /**
     * @return array<string, Input>
     */
    public function inputs(Context $context): array
    {
        return [
            'directory' => $this->directory(),
            'php' => new Input(new Selection('patterns', ['bench/*.php'], [], '', true), 'php.tokens'),
        ];
    }

    /**
     * Shares exactly the same directory selection with Composer rule generation.
     */
    public function directory(): Input
    {
        return new Input(new Selection(
            'directories',
            ['bench'],
            ['bench/*/*'],
            '',
            false,
            'PHPBench requires the bench/ directory. Create bench/Bench.php with a PHPBench benchmark.'
        ));
    }

    /**
     * @throws RuntimeException
     * @throws JsonException
     * @throws Exception
     */
    public function evaluate(InputSet $inputs, Context $context): Plan
    {
        $listing = $inputs->get('directory')->directories['bench'] ?? null;
        $findings = $listing === null ? [] : (new BenchmarkLayout())->findings($listing);
        foreach ($inputs->get('php')->files as $path => $file) {
            $tokens = $file->value();
            if (!$file->readable || !$tokens instanceof Tokens) {
                throw new PolicyException('Cannot inspect PHPBench references in ' . $path . '. Check file permissions and register the php.tokens structurer.');
            }
            if (!(new PhpBenchReference())->exists($tokens)) {
                $findings[] = new Finding($path, 'phpbench.namespace', 'required', $path . ' does not reference the PhpBench namespace in PHP code. Replace the custom benchmark with a PHPBench benchmark, for example use PhpBench\\Attributes as Bench; and #[Bench\\Revs(1)] on a bench method. Comments and strings do not count.');
            }
        }
        return new Plan($findings, []);
    }
}
