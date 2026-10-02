<?php

declare(strict_types=1);

namespace Tests\Unit\TreeGuard\Analysis;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\TreeGuard\Analysis\RequiredFileInspector;
use Toolkit\TreeGuard\Analysis\Violation;
use Toolkit\TreeGuard\Config\RuleConfig;
use Toolkit\TreeGuard\Filesystem\DirectoryListing;
use Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver;

/**
 * @covers \Toolkit\TreeGuard\Analysis\RequiredFileInspector
 * @uses \Toolkit\TreeGuard\Filesystem\DirectoryListing
 * @uses \Toolkit\TreeGuard\Config\RuleConfig
 * @uses \Toolkit\TreeGuard\Filesystem\TreeGuardPathResolver
 * @uses \Toolkit\TreeGuard\Analysis\Violation
 */
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
