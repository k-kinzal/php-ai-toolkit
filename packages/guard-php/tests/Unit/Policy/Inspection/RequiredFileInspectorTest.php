<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Inspection;

use Guard\Collect\DirectoryListing;
use Guard\Collect\Filesystem\Path as PathResolver;
use Guard\Config\Value\DirectoryRuleConfig;
use Guard\Policy\Inspection\RequiredFileInspector;
use Guard\Reporting\DirectoryViolation;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Inspection\RequiredFileInspector
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\Filesystem\Path
 * @uses \Guard\Config\Value\DirectoryRuleConfig
 * @uses \Guard\Reporting\DirectoryViolation
 */
#[CoversClass(RequiredFileInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(PathResolver::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(DirectoryViolation::class)]
final class RequiredFileInspectorTest extends TestCase
{
    public function testInspectPassesWhenRequiredFilesExist(): void
    {
        $rule = new DirectoryRuleConfig('skills/*', null, null, null, null, null, null, null, null, ['SKILL.md'], false, null, null);
        $listing = new DirectoryListing('skills/setup', ['SKILL.md', 'template.yaml'], []);

        self::assertSame([], (new RequiredFileInspector())->inspect($rule, $listing));
    }

    public function testInspectReportsEachMissingRequiredFile(): void
    {
        $rule = new DirectoryRuleConfig('skills/*', null, null, null, null, null, null, null, null, ['SKILL.md', 'template.yaml'], false, null, null);
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
        $rule = new DirectoryRuleConfig('skills/*', null, null, null, null, null, null, null, null, null, false, null, null);
        $listing = new DirectoryListing('skills/setup', [], []);

        self::assertSame([], (new RequiredFileInspector())->inspect($rule, $listing));
    }
}
