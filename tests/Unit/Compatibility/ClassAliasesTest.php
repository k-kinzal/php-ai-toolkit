<?php

declare(strict_types=1);

namespace Tests\Unit\Compatibility;

use function class_exists;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

use function str_replace;

/**
 * @coversNothing
 */
#[CoversNothing]
final class ClassAliasesTest extends TestCase
{
    public function testLegacyResponsibilityNamesResolveToTheirCurrentClasses(): void
    {
        $broadCatchRule = str_replace('/', '\\', 'PhpAiToolkit/PhpStan/Rule/ForbidBroadCatchRule');
        self::assertFalse(class_exists($broadCatchRule, false));
        self::assertTrue(class_exists($broadCatchRule));
        self::assertTrue(class_exists(str_replace('/', '\\', 'PhpAiToolkit/PhpStan/Rule/PhpDoc/MissingExampleErrorBuilder')));
        self::assertTrue(class_exists(str_replace('/', '\\', 'PhpAiToolkit/PhpStan/Rule/Shared/PathMarkerSplitter')));
        self::assertTrue(class_exists(str_replace('/', '\\', 'PhpAiToolkit/PhpStan/Rule/TestClass/FilenameExclusionMatcher')));
        self::assertTrue(class_exists(str_replace('/', '\\', 'PhpAiToolkit/PhpUnit/TestReporter/TestIssueFormatter')));
        self::assertTrue(class_exists(str_replace('/', '\\', 'PhpAiToolkit/DocGen/Analysis/Parse/ClassLikeBuilder')));
        self::assertTrue(class_exists(str_replace('/', '\\', 'PhpAiToolkit/DocGen/Render/SocialCard')));
        self::assertTrue(class_exists(str_replace('/', '\\', 'PhpAiToolkit/DocGen/Render/Page/BreadcrumbHtml')));
    }
}
