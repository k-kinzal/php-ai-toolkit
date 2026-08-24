<?php

declare(strict_types=1);

namespace PhpAiToolkit\DocGen\Parallel;

use function array_key_exists;
use function array_map;
use function count;
use function fclose;
use function fwrite;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;
use function ob_end_clean;
use function ob_get_level;
use function pcntl_fork;
use function pcntl_waitpid;
use function pcntl_wexitstatus;
use function pcntl_wifexited;

use PhpAiToolkit\DocGen\DocGenException;

use function preg_match;
use function serialize;
use function sprintf;
use function stream_get_contents;

use const STREAM_IPPROTO_IP;
use const STREAM_PF_UNIX;
use const STREAM_SOCK_STREAM;

use function stream_socket_pair;
use function strlen;
use function strpos;
use function substr;

use Throwable;

use function unserialize;

/**
 * Runs the jobs of one generation phase in forked worker processes.
 *
 * Every job is forked once, works on its own share of the phase, and hands
 * its result back over a socket of its own. Results are returned in job
 * order rather than in the order the workers happened to finish, because
 * the site a run writes has to be the same site whatever the machine
 * scheduled first.
 *
 * A pool that cannot fork, or that has nothing worth splitting, runs the
 * jobs in this process instead. That is not a fallback for the sake of it:
 * it runs the same callable the workers run, so a sequential run and a
 * parallel run cannot drift apart.
 */
final class WorkerPool
{
    /** @readonly */
    private ForkSupport $fork;

    /**
     * Creates a worker pool from its fork availability check.
     */
    public function __construct(?ForkSupport $fork = null)
    {
        $this->fork = $fork ?? new ForkSupport();
    }

    /**
     * Runs every job and returns their results in job order.
     *
     * Results come back from another process, so they are returned as the
     * unvalidated values they are: what a phase expects of its own workers
     * is for that phase to state, not for the pool to assume.
     *
     * @template TJob
     *
     * @param list<TJob> $jobs
     * @param callable(TJob): mixed $work
     *
     * @return list<mixed>
     *
     * @throws DocGenException when a worker dies without a usable result
     */
    public function map(array $jobs, callable $work): array
    {
        if (count($jobs) < 2 || !$this->fork->isAvailable()) {
            return array_map($work, $jobs);
        }

        $workers = [];
        foreach ($jobs as $index => $job) {
            $sockets = [];
            foreach ($workers as $started) {
                $sockets[] = $started['socket'];
            }

            $worker = $this->start($job, $work, $sockets);
            if ($worker === null) {
                return $this->finish($workers, array_map($work, $this->remaining($jobs, $index)));
            }

            $workers[] = $worker;
        }

        return $this->finish($workers, []);
    }

    /**
     * Forks one worker, or returns null when it could not be forked.
     *
     * The child closes the sockets of the workers forked before it, because
     * a socket left open in any process keeps the parent waiting for an end
     * of file that never comes. It drops the output buffers it inherited
     * for the same reason: a worker must not emit what the process that
     * forked it had buffered.
     *
     * @template TJob
     *
     * @param TJob $job
     * @param callable(TJob): mixed $work
     * @param list<resource> $openSockets the parent ends already handed out
     *
     * @return ?array{pid: int, socket: resource}
     */
    public function start($job, callable $work, array $openSockets): ?array
    {
        $pair = @stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);
        if (!is_array($pair)) {
            return null;
        }

        $pid = pcntl_fork();
        if ($pid === -1) {
            fclose($pair[0]);
            fclose($pair[1]);

            return null;
        }

        if ($pid === 0) {
            fclose($pair[0]);
            foreach ($openSockets as $socket) {
                fclose($socket);
            }

            while (ob_get_level() > 0) {
                ob_end_clean();
            }

            @fwrite($pair[1], $this->payload($job, $work));
            fclose($pair[1]);

            exit(0);
        }

        fclose($pair[1]);

        return ['pid' => $pid, 'socket' => $pair[0]];
    }

    /**
     * Works one job and returns what its worker sends back.
     *
     * A job that throws reports the failure to the parent instead of
     * writing it to a terminal nobody is reading, so the run stops with the
     * message rather than with a site missing whatever that worker held.
     *
     * @template TJob
     *
     * @param TJob $job
     * @param callable(TJob): mixed $work
     */
    public function payload($job, callable $work): string
    {
        try {
            return $this->framed(serialize(['ok' => true, 'result' => $work($job)]));
        } catch (Throwable $exception) {
            return $this->framed(serialize(['ok' => false, 'result' => $exception->getMessage()]));
        }
    }

    /**
     * Puts the byte count of a result in front of it.
     *
     * A worker that dies while writing leaves the parent with the first
     * part of a result and no way to tell that from a whole one. The count
     * makes a short result recognisable before anything tries to read it
     * as one, which is what keeps a half-finished run from being reported
     * as a finished one.
     */
    public function framed(string $body): string
    {
        return strlen($body) . "\n" . $body;
    }

    /**
     * Reads the result out of a framed payload, or returns null.
     *
     * @param string|false $payload what the worker wrote to its socket
     *
     * @return ?array{ok: bool, result: mixed}
     */
    public function unframed($payload): ?array
    {
        if (!is_string($payload)) {
            return null;
        }

        $break = strpos($payload, "\n");
        if ($break === false || preg_match('/^\d+$/', substr($payload, 0, $break)) !== 1) {
            return null;
        }

        $body = substr($payload, $break + 1);
        if (strlen($body) !== (int) substr($payload, 0, $break)) {
            return null;
        }

        $decoded = unserialize($body);
        if (!is_array($decoded)
            || !array_key_exists('ok', $decoded)
            || !is_bool($decoded['ok'])
            || !array_key_exists('result', $decoded)
        ) {
            return null;
        }

        return ['ok' => $decoded['ok'], 'result' => $decoded['result']];
    }

    /**
     * Collects every worker result and reaps every worker.
     *
     * Every worker is waited for even after one of them failed, because a
     * process left unreaped outlives the run that started it.
     *
     * @param list<array{pid: int, socket: resource}> $workers
     * @param list<mixed> $tail results of the jobs this process took over
     *
     * @return list<mixed>
     *
     * @throws DocGenException when a worker died without a usable result
     */
    public function finish(array $workers, array $tail): array
    {
        $results = [];
        $failure = null;
        foreach ($workers as $worker) {
            $payload = @stream_get_contents($worker['socket']);
            fclose($worker['socket']);
            $status = 0;
            pcntl_waitpid($worker['pid'], $status);
            $result = $this->result($payload, is_int($status) ? $status : -1);
            if ($result['ok'] !== true) {
                $failure = $failure ?? $result['reason'];
                continue;
            }

            $results[] = $result['result'];
        }

        if ($failure !== null) {
            throw new DocGenException(sprintf('A documentation worker failed: %s', $failure));
        }

        foreach ($tail as $result) {
            $results[] = $result;
        }

        return $results;
    }

    /**
     * Reads what one worker sent back, or reports why it is unusable.
     *
     * @param string|false $payload what the worker wrote to its socket
     * @param int $status the wait status of the worker process
     *
     * @return array{ok: bool, result: mixed, reason: ?string}
     */
    public function result($payload, int $status): array
    {
        if (!pcntl_wifexited($status) || pcntl_wexitstatus($status) !== 0) {
            return ['ok' => false, 'result' => null, 'reason' => 'the worker process stopped before it finished'];
        }

        $decoded = $this->unframed($payload);
        if ($decoded === null) {
            return ['ok' => false, 'result' => null, 'reason' => 'the worker process sent no result'];
        }

        if ($decoded['ok'] !== true) {
            $reason = $decoded['result'] ?? null;

            return ['ok' => false, 'result' => null, 'reason' => is_string($reason) ? $reason : 'the job could not be worked'];
        }

        return ['ok' => true, 'result' => $decoded['result'] ?? null, 'reason' => null];
    }

    /**
     * Returns the jobs from one position onwards.
     *
     * @template TJob
     *
     * @param list<TJob> $jobs
     *
     * @return list<TJob>
     */
    public function remaining(array $jobs, int $from): array
    {
        $rest = [];
        foreach ($jobs as $index => $job) {
            if ($index >= $from) {
                $rest[] = $job;
            }
        }

        return $rest;
    }
}
