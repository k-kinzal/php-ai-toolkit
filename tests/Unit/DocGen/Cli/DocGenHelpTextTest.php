<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cli;

use PhpAiToolkit\DocGen\Cli\DocGenHelpText;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\DocGen\Cli\DocGenHelpText
 */
#[CoversClass(DocGenHelpText::class)]
final class DocGenHelpTextTest extends TestCase
{
    public function testTextDescribesUsageLine(): void
    {
        self::assertStringContainsString('Usage: doc-gen', (new DocGenHelpText())->text());
    }

    public function testTextListsEveryOption(): void
    {
        $text = (new DocGenHelpText())->text();

        self::assertStringContainsString('--packages=GLOBS', $text);
        self::assertStringContainsString('--exclude=GLOBS', $text);
        self::assertStringContainsString('--output', $text);
        self::assertStringContainsString('--title=TEXT', $text);
        self::assertStringContainsString('--vendor[=GLOBS]', $text);
        self::assertStringContainsString('--vendor-dev[=GLOBS]', $text);
        self::assertStringContainsString('--deptrac=FILE', $text);
        self::assertStringContainsString('--coverage', $text);
        self::assertStringContainsString('--base-url=URL', $text);
        self::assertStringContainsString('--repository=URL', $text);
        self::assertStringContainsString('--diff=RANGE', $text);
        self::assertStringContainsString('--serve', $text);
        self::assertStringContainsString('-h, --help', $text);
        self::assertStringContainsString('-V, --version', $text);
    }

    public function testTextNamesNoConfigurationFile(): void
    {
        self::assertStringNotContainsString('--config', (new DocGenHelpText())->text());
    }

    public function testPurposeStatesWhatDocGenDoes(): void
    {
        self::assertStringContainsString('Usage: doc-gen [options]', (new DocGenHelpText())->purpose());
    }

    public function testScopeOptionsListWhatIsDocumented(): void
    {
        $text = (new DocGenHelpText())->scopeOptions();

        self::assertStringContainsString('--packages=GLOBS', $text);
        self::assertStringContainsString('--exclude=GLOBS', $text);
        self::assertStringContainsString('--output=DIR', $text);
        self::assertStringContainsString('--title=TEXT', $text);
    }

    public function testSiteOptionsListWhatThePagesSayAboutTheProject(): void
    {
        $text = (new DocGenHelpText())->siteOptions();

        self::assertStringContainsString('--deptrac=FILE', $text);
        self::assertStringContainsString('--coverage=DIR', $text);
        self::assertStringContainsString('--base-url=URL', $text);
        self::assertStringContainsString('--repository=URL', $text);
    }

    public function testDiffOptionsListTheComparedRevisions(): void
    {
        $text = (new DocGenHelpText())->diffOptions();

        self::assertStringContainsString('--diff=RANGE', $text);
        self::assertStringContainsString('--base=REVISION', $text);
        self::assertStringContainsString('--head=REVISION', $text);
    }

    public function testCacheOptionsListWhatIsRememberedBetweenRuns(): void
    {
        $text = (new DocGenHelpText())->cacheOptions();

        self::assertStringContainsString('--cache-dir=DIR', $text);
        self::assertStringContainsString('--no-cache', $text);
        self::assertStringContainsString('--clear-cache', $text);
    }

    public function testRunOptionsListHowARunIsCarriedOut(): void
    {
        self::assertStringContainsString('--jobs=N', (new DocGenHelpText())->runOptions());
    }
}
