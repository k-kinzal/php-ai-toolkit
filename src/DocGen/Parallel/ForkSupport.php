<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Parallel;

use function function_exists;
use function is_array;
use function opcache_get_status;

/**
 * Decides whether this process may fork workers.
 *
 * Forking is what makes parallel generation worth doing at all: a forked
 * worker inherits the analyzed project through copy-on-write, so nothing
 * has to be serialized to hand it the model. It is also the mechanism with
 * the most ways to be unavailable, and every one of them has to be found
 * before the first fork rather than after a half-written site.
 */
final class ForkSupport
{
    /**
     * The process functions a worker pool cannot work without.
     *
     * @var list<string>
     */
    public const REQUIRED_FUNCTIONS = ['pcntl_fork', 'pcntl_waitpid', 'pcntl_wifexited', 'pcntl_wexitstatus', 'stream_socket_pair'];

    /**
     * Reports whether workers may be forked.
     */
    public function isAvailable(): bool
    {
        return $this->unavailableReason() === null;
    }

    /**
     * Returns the functions a run needs to fork its workers.
     *
     * The list is read through a method rather than the constant itself,
     * because what a build of PHP provides is a question about the machine
     * a run happens on and not about the analyzer that reads this file.
     *
     * @return list<string>
     */
    public function requiredFunctions(): array
    {
        return self::REQUIRED_FUNCTIONS;
    }

    /**
     * Explains why workers may not be forked, or returns null when they may.
     */
    public function unavailableReason(): ?string
    {
        foreach ($this->requiredFunctions() as $function) {
            if (!function_exists($function)) {
                return 'the pcntl extension is not available';
            }
        }

        return $this->isSharedCodeCacheEnabled() ? 'OPcache or JIT is enabled for the CLI' : null;
    }

    /**
     * Reports whether a code cache is shared with the forked children.
     *
     * OPcache and the JIT keep compiled code in shared memory that is not
     * safe to populate from several forked children at once, so a run that
     * has either of them turned on stays sequential rather than risking a
     * site generated from corrupted code.
     */
    public function isSharedCodeCacheEnabled(): bool
    {
        if (!function_exists('opcache_get_status')) {
            return false;
        }

        $status = @opcache_get_status(false);
        if ($status === false) {
            return false;
        }

        $jit = $status['jit'] ?? null;

        return ($status['opcache_enabled'] ?? false) === true || (is_array($jit) && ($jit['enabled'] ?? false) === true);
    }
}
