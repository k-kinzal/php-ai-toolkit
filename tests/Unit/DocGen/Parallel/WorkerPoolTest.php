<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Parallel;

use PhpAiToolkit\DocGen\DocGenException;
use PhpAiToolkit\DocGen\Parallel\ForkSupport;
use PhpAiToolkit\DocGen\Parallel\WorkerPool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(WorkerPool::class)]
#[UsesClass(ForkSupport::class)]
final class WorkerPoolTest extends TestCase
{
    public function testMapReturnsOneResultPerJobInJobOrder(): void
    {
        $results = (new WorkerPool())->map([[1, 2], [3], [4, 5, 6]], static fn (array $job): int => array_sum($job));

        self::assertSame([3, 3, 15], $results);
    }

    public function testMapWorksASingleJobInThisProcessWithoutForking(): void
    {
        $results = (new WorkerPool())->map([[1, 2, 3]], static fn (array $job): int => getmypid() === false ? 0 : getmypid());

        self::assertSame([getmypid()], $results);
        self::assertSame([], (new WorkerPool())->map([], static fn (array $job): int => 1));
    }

    #[RequiresPhpExtension('pcntl')]
    public function testMapWorksEveryJobInAProcessOfItsOwn(): void
    {
        $pids = array_map(
            static fn ($pid): int => is_int($pid) ? $pid : 0,
            (new WorkerPool())->map([[1], [2], [3]], static fn (array $job): int => (int) getmypid()),
        );

        self::assertCount(3, array_unique($pids));
        self::assertNotContains(getmypid(), $pids);
    }

    #[RequiresPhpExtension('pcntl')]
    public function testMapReportsTheFailureOfAWorkerInsteadOfLosingItsJob(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('A documentation worker failed: the second job cannot be worked');

        (new WorkerPool())->map([[1], [2]], static function (array $job): int {
            if ($job[0] === 2) {
                throw new DocGenException('the second job cannot be worked');
            }

            return $job[0];
        });
    }

    #[RequiresPhpExtension('pcntl')]
    public function testStartForksAWorkerThatSendsBackWhatItWorked(): void
    {
        $pool = new WorkerPool();

        $worker = $pool->start([2, 3], static fn (array $job): int => array_sum($job), []);

        self::assertIsArray($worker);
        self::assertNotSame(getmypid(), $worker['pid']);
        $payload = stream_get_contents($worker['socket']);
        fclose($worker['socket']);
        $status = 0;
        pcntl_waitpid($worker['pid'], $status);

        self::assertSame(['ok' => true, 'result' => 5, 'reason' => null], $pool->result($payload, is_int($status) ? $status : -1));
    }

    public function testPayloadCarriesTheResultOfAJobOrTheReasonItFailed(): void
    {
        $pool = new WorkerPool();

        self::assertSame(['ok' => true, 'result' => 6], $pool->unframed($pool->payload([1, 2, 3], static fn (array $job): int => array_sum($job))));
        self::assertSame(
            ['ok' => false, 'result' => 'nothing to work with'],
            $pool->unframed($pool->payload([], static function (array $job): int {
                throw new DocGenException('nothing to work with');
            })),
        );
    }

    public function testFramedPutsTheByteCountOfAResultInFrontOfIt(): void
    {
        self::assertSame("5\nabcde", (new WorkerPool())->framed('abcde'));
        self::assertSame("0\n", (new WorkerPool())->framed(''));
    }

    public function testUnframedRejectsAnythingThatIsNotAWholeResult(): void
    {
        $pool = new WorkerPool();
        $whole = $pool->framed(serialize(['ok' => true, 'result' => null]));

        self::assertSame(['ok' => true, 'result' => null], $pool->unframed($whole));
        self::assertNull($pool->unframed(substr($whole, 0, -3)));
        self::assertNull($pool->unframed(false));
        self::assertNull($pool->unframed(''));
        self::assertNull($pool->unframed('no frame at all'));
        self::assertNull($pool->unframed("x\nbody"));
        self::assertNull($pool->unframed($pool->framed(serialize('not an array'))));
        self::assertNull($pool->unframed($pool->framed(serialize(['ok' => true]))));
    }

    public function testFinishReturnsTheResultsOfTheJobsThisProcessTookOver(): void
    {
        self::assertSame(['a', 'b'], (new WorkerPool())->finish([], ['a', 'b']));
    }

    public function testResultRejectsAWorkerThatStoppedOrSentNothingUsable(): void
    {
        $pool = new WorkerPool();

        self::assertSame(
            ['ok' => false, 'result' => null, 'reason' => 'the worker process stopped before it finished'],
            $pool->result($pool->framed(serialize(['ok' => true, 'result' => 1])), 256),
        );
        self::assertSame(['ok' => false, 'result' => null, 'reason' => 'the worker process sent no result'], $pool->result('', 0));
        self::assertSame(['ok' => false, 'result' => null, 'reason' => 'the worker process sent no result'], $pool->result(false, 0));
        self::assertSame(['ok' => false, 'result' => null, 'reason' => 'the worker process sent no result'], $pool->result('not serialized', 0));
        self::assertSame(['ok' => false, 'result' => null, 'reason' => 'the job could not be worked'], $pool->result($pool->framed(serialize(['ok' => false, 'result' => 7])), 0));
        self::assertSame(['ok' => true, 'result' => 'done', 'reason' => null], $pool->result($pool->framed(serialize(['ok' => true, 'result' => 'done'])), 0));
    }

    public function testRemainingReturnsTheJobsFromOnePositionOnwards(): void
    {
        $pool = new WorkerPool();

        self::assertSame([['c'], ['d']], $pool->remaining([['a'], ['b'], ['c'], ['d']], 2));
        self::assertSame([], $pool->remaining([['a']], 5));
    }
}
