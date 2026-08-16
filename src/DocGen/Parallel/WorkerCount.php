<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Parallel;

use function max;
use function min;

/**
 * Turns the worker count a run asked for into the count it will use.
 *
 * A run that says nothing gets a count derived from the cores of the
 * machine it runs on, so the same command is as parallel as the machine
 * allows without anybody having to tune it. A run that names a count gets
 * that count, because the machine is not always the only thing deciding:
 * a continuous integration job shares its cores with other jobs, and a
 * reproducible measurement needs one worker and no scheduler at all.
 */
final class WorkerCount
{
    /**
     * The most workers a run starts, however many cores a machine has.
     */
    public const MAXIMUM = 16;

    /** @readonly */
    private CpuCoreCounter $cores;

    /**
     * Creates a worker count from the core counter of the machine.
     */
    public function __construct(?CpuCoreCounter $cores = null)
    {
        $this->cores = $cores ?? new CpuCoreCounter();
    }

    /**
     * Returns how many workers a phase may use, at least one.
     *
     * @param ?int $requested the count a run asked for, or null for the default
     */
    public function resolve(?int $requested): int
    {
        if ($requested !== null) {
            return max($requested, 1);
        }

        return $this->defaultCount($this->cores->count());
    }

    /**
     * Returns the worker count a machine with the given cores defaults to.
     *
     * One core is left to the operating system and to the process that
     * waits for the workers, so a generation never takes a machine over
     * completely, and the count is capped because the phases spend their
     * time in the parser and the filesystem rather than only on the CPU.
     */
    public function defaultCount(int $cores): int
    {
        return max(min($cores - 1, self::MAXIMUM), 1);
    }
}
