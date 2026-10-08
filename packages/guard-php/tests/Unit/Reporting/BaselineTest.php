<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Guard\Diagnostic\Finding;
use Guard\Diagnostic\PolicyException;
use Guard\Reporting\Baseline;
use JsonException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * @covers \Guard\Reporting\Baseline
 * @uses \Guard\Diagnostic\Finding
 * @uses \Guard\Reporting\BaselineMatch
 * @uses \Guard\Config\Schema
 * @uses \Guard\Diagnostic\PolicyException
 */
#[\PHPUnit\Framework\Attributes\CoversClass(Baseline::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Finding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\BaselineMatch::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Schema::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(PolicyException::class)]
final class BaselineTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testCaptureIsDeterministicAndPreservesExactCounts(): void
    {
        $baseline = new Baseline();
        $a = new Finding('src/あ.php', 'metrics.file_lines', 'required', 'Has 501 lines; maximum 500. Split the file.');
        $b = new Finding('b.json', 'mode', 'recommended', 'Set mode to safe.', true);
        $json = $baseline->capture([$b, $a, $a]);
        self::assertSame($json, $baseline->capture([$a, $b, $a]));
        $entries = $baseline->entries($json);
        self::assertCount(2, $entries);
        self::assertSame(2, $entries[1]['count']);
        self::assertSame('warning', $entries[0]['level']);
        self::assertStringContainsString('src/あ.php', $json);
    }

    /**
     * @throws JsonException
     */
    public function testFilterLeavesAdditionalOccurrencesWorseningAndOtherPathsVisible(): void
    {
        $baseline = new Baseline();
        $old = new Finding('a.php', 'metrics.file_lines', 'required', 'Has 501 lines; maximum 500. Split the file.');
        $worse = new Finding('a.php', 'metrics.file_lines', 'required', 'Has 502 lines; maximum 500. Split the file.');
        $other = new Finding('b.php', $old->rule, $old->level, $old->message);
        $json = $baseline->capture([$old]);
        $match = $baseline->filter([$old, $old, $worse, $other], $json);
        self::assertSame([$old, $worse, $other], $match->findings);
        self::assertSame(1, $match->suppressed);
        self::assertSame(0, $match->unmatched);
        self::assertSame(1, $baseline->filter([], $json)->unmatched);
    }

    /**
     * @throws JsonException
     */
    public function testFilterDoesNotHidePromotedWarningsOrChangedRuleIdentifiers(): void
    {
        $baseline = new Baseline();
        $warning = new Finding('a', 'rule', 'recommended', 'Problem. Fix it.');
        $error = new Finding('a', 'rule', 'required', $warning->message);
        $other = new Finding('a', 'other', 'recommended', $warning->message);
        self::assertSame([$error, $other], $baseline->filter([$error, $other], $baseline->capture([$warning]))->findings);
    }

    /**
     * @throws JsonException
     */
    public function testEntriesRejectDuplicateEntries(): void
    {
        $json = (new Baseline())->capture([new Finding('a', 'rule', 'required', 'Problem. Fix it.')]);
        $entry = (new Baseline())->entries($json)[0];
        $data = ['version' => 1, 'entries' => [$entry, $entry]];
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Duplicate baseline entry');
        (new Baseline())->entries(json_encode($data, JSON_THROW_ON_ERROR));
    }

    /**
     * @dataProvider providerInvalidBaselines
     * @throws JsonException
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('providerInvalidBaselines')]
    public function testEntriesRejectMalformedOrUnsupportedBaselines(string $json): void
    {
        $this->expectException(RuntimeException::class);
        (new Baseline())->entries($json);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function providerInvalidBaselines(): iterable
    {
        yield 'missing version' => ['{"entries":[]}'];
        yield 'unsupported version' => ['{"version":2,"entries":[]}'];
        yield 'unknown property' => ['{"version":1,"entries":[],"ignoreAll":true}'];
        yield 'wrong entries shape' => ['{"version":1,"entries":{"rule":{}}}'];
        yield 'invalid severity' => ['{"version":1,"entries":[{"path":"a","rule":"b","level":"all","message":"m","count":1}]}'];
        yield 'zero count' => ['{"version":1,"entries":[{"path":"a","rule":"b","level":"error","message":"m","count":0}]}'];
        yield 'missing message' => ['{"version":1,"entries":[{"path":"a","rule":"b","level":"error","count":1}]}'];
    }

    public function testEntryRejectsUnknownFields(): void
    {
        $this->expectException(PolicyException::class);
        (new Baseline())->entry(['ignore' => true]);
    }

    /**
     * @throws JsonException
     */
    public function testKeyCannotCollideOnEmbeddedSeparators(): void
    {
        $baseline = new Baseline();
        $entry = ['path' => "a\nb", 'rule' => 'c', 'level' => 'error', 'message' => 'Problem. Fix it.'];
        self::assertNotSame($baseline->key($entry), $baseline->key(array_replace($entry, ['path' => 'a', 'rule' => "b\nc"])));
    }
}
