<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PhpAiToolkit\TreeGuard\Analysis\RequiredFileInspector;
use PhpAiToolkit\TreeGuard\Analysis\Violation;
use PhpAiToolkit\TreeGuard\Config\RuleConfig;
use PhpAiToolkit\TreeGuard\Filesystem\DirectoryListing;
use PhpAiToolkit\TreeGuard\Filesystem\TreeGuardPathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RequiredFileInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(RuleConfig::class)]
#[UsesClass(TreeGuardPathResolver::class)]
#[UsesClass(Violation::class)]
final class RequiredFileInspectorTest extends TestCase
{
    public function testInspectPassesWhenRequiredFilesExist(): void
    {
        $rule = new RuleConfig('skills/*', null, null, null, null, null, null, null, null, ['SKILL.md'], false, null, null);
        $listing = new DirectoryListing('skills/setup', ['SKILL.md', 'template.yaml'], []);

        self::assertSame([], (new RequiredFileInspector())->inspect($rule, $listing));
    }

    public function testInspectReportsEachMissingRequiredFile(): void
    {
        $rule = new RuleConfig('skills/*', null, null, null, null, null, null, null, null, ['SKILL.md', 'template.yaml'], false, null, null);
        $listing = new DirectoryListing('skills/setup', ['template.yaml'], []);

        $violations = (new RequiredFileInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('skills/setup/SKILL.md', $violations[0]->path);
        self::assertSame('missing_required_file', $violations[0]->rule);
        self::assertSame('skills/*', $violations[0]->pattern);
        self::assertNull($violations[0]->actual);
        self::assertSame('Directory "skills/setup" is missing required file "SKILL.md". Create it.', $violations[0]->message);
    }

    public function testInspectSkipsAbsentRequireList(): void
    {
        $rule = new RuleConfig('skills/*', null, null, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('skills/setup', [], []);

        self::assertSame([], (new RequiredFileInspector())->inspect($rule, $listing));
    }
}
