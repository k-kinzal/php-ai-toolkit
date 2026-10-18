<?php

declare(strict_types=1);

namespace Tests\Unit\Reporting;

use DecisionsLint\Diagnostic\Finding;
use DecisionsLint\Lint\Report;
use DecisionsLint\Reporting\CheckstyleReporter;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Reporting\CheckstyleReporter
 * @uses \DecisionsLint\Diagnostic\Finding
 * @uses \DecisionsLint\Lint\Report
 */
#[CoversClass(CheckstyleReporter::class)]
#[UsesClass(Finding::class)]
#[UsesClass(Report::class)]
final class CheckstyleReporterTest extends TestCase
{
    /**
     * @throws JsonException
     */
    public function testRenderWritesOneFileElementPerPath(): void
    {
        $report = new Report([
            new Finding('src/A.php', 12, 5, 'cohesion.method', 'error', 'App\A::foo', 'Misplaced "foo" & co.', 'Move <it>.', 0.9),
            new Finding('src/A.php', 20, 1, 'naming.vague', 'warning', 'App\A::bar', 'Vague.', 'Rename.', 0.6),
        ], 1, 2, 1, [], 'error');
        self::assertSame(<<<'XML'
            <?xml version="1.0" encoding="UTF-8"?>
            <checkstyle version="decisions-lint">
              <file name="src/A.php">
                <error line="12" column="5" severity="error" message="Misplaced &quot;foo&quot; &amp; co. Fix: Move &lt;it&gt;." source="cohesion.method"/>
                <error line="20" column="1" severity="warning" message="Vague. Fix: Rename." source="naming.vague"/>
              </file>
            </checkstyle>

            XML, (new CheckstyleReporter())->render($report, true));
    }

    public function testEscapeProtectsAttributeCharacters(): void
    {
        self::assertSame('&lt;a href=&quot;x&quot;&gt;&amp;&apos;', (new CheckstyleReporter())->escape('<a href="x">&\''));
    }
}
