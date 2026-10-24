<?php

declare(strict_types=1);

namespace Tests\Unit\Policy\Inspection;

use Guard\Input\DirectoryListing;
use Guard\Input\Path;
use Guard\Policy\Definition\DirectoryRuleConfig;
use Guard\Policy\Diagnostic\DirectoryViolation;
use Guard\Policy\Inspection\RequiredDirectoryInspector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Policy\Inspection\RequiredDirectoryInspector
 * @uses \Guard\Input\DirectoryListing
 * @uses \Guard\Input\Path
 * @uses \Guard\Policy\Definition\DirectoryRuleConfig
 * @uses \Guard\Policy\Diagnostic\DirectoryViolation
 */
#[CoversClass(RequiredDirectoryInspector::class)]
#[UsesClass(DirectoryListing::class)]
#[UsesClass(Path::class)]
#[UsesClass(DirectoryRuleConfig::class)]
#[UsesClass(DirectoryViolation::class)]
final class RequiredDirectoryInspectorTest extends TestCase
{
    public function testInspectRequiresDirectDirectoriesAndRejectsSameNamedFiles(): void
    {
        $rule = new DirectoryRuleConfig('spec/**', null, null, null, null, null, null, null, null, null, false, null, null, ['Context', 'Fixtures']);
        $listing = new DirectoryListing('spec/Account', ['Context'], ['Fixtures', 'context']);

        $violations = (new RequiredDirectoryInspector())->inspect($rule, $listing);

        self::assertCount(1, $violations);
        self::assertSame('spec/Account/Context', $violations[0]->path);
        self::assertSame('missing_required_dir', $violations[0]->rule);
        self::assertSame('spec/**', $violations[0]->pattern);
        self::assertSame('Directory "spec/Account" is missing required subdirectory "Context". Create it.', $violations[0]->message);
    }

    public function testInspectAllowsAbsentRequirements(): void
    {
        $rule = new DirectoryRuleConfig('.', null, null, null, null, null, null, null, null, null, false, null, null);

        self::assertSame([], (new RequiredDirectoryInspector())->inspect($rule, new DirectoryListing('.', [], [])));
    }

    public function testInspectAllowsEmptyRequirements(): void
    {
        $rule = new DirectoryRuleConfig('.', null, null, null, null, null, null, null, null, null, false, null, null, []);

        self::assertSame([], (new RequiredDirectoryInspector())->inspect($rule, new DirectoryListing('.', [], [])));
    }

    public function testInspectAcceptsExistingRequiredDirectory(): void
    {
        $rule = new DirectoryRuleConfig('spec/**', null, null, null, null, null, null, null, null, null, false, null, null, ['Context']);

        self::assertSame([], (new RequiredDirectoryInspector())->inspect($rule, new DirectoryListing('spec', [], ['Context'])));
    }
}
