<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use Guard\Policy\Diagnostic\RuleDescription;
use Guard\Reporting\RuleReporter;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Reporting\RuleReporter
 * @uses \Guard\Policy\Diagnostic\RuleDescription
 */
#[CoversClass(RuleReporter::class)]
#[UsesClass(RuleDescription::class)]
final class RuleReporterTest extends TestCase
{
    public function testFilterSearchesMessagesAndPathsWithoutCaseSensitivity(): void
    {
        $a = new RuleDescription('a', 'app.json', 'required', true, 'Prevent drift.');
        $b = new RuleDescription('b', 'test.xml', 'recommended', false, 'Catch leaks.');
        $reporter = new RuleReporter();
        self::assertSame([$a], $reporter->filter([$a, $b], 'DRIFT'));
        self::assertSame([$b], $reporter->filter([$a, $b], 'test.xml'));
        self::assertSame([], $reporter->filter([$a, $b], 'missing'));
        self::assertSame([$a, $b], $reporter->filter([$a, $b], ''));
    }

    /**
     * @throws JsonException
     */
    public function testRenderEscapesHumanMarkupAndKeepsJsonAndAiPlain(): void
    {
        $rule = new RuleDescription('x', '<info>file</info>', 'required', true, '<error>literal</error>');
        $reporter = new RuleReporter();
        self::assertStringContainsString('<error>literal</error>', $reporter->render([$rule], 'text'));
        self::assertStringNotContainsString("\033", $reporter->render([$rule], 'ai', true));
        $json = $reporter->render([$rule], 'json', true);
        self::assertJson($json);
        self::assertStringContainsString('"count": 1', $json);
        self::assertStringContainsString('"fixable": true', $json);
    }

    public function testRowsPreservesTheSelectedOrder(): void
    {
        $rule = new RuleDescription('id', 'target', 'required', false, 'Remove the unsupported entry.');
        self::assertSame([$rule->toArray()], (new RuleReporter())->rows([$rule]));
    }

}
