<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Architecture;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermErrorBuilder;
use Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermInspector;
use Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermRestrictions;
use Toolkit\PhpStan\Rule\Shared\LineOrderedErrors;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher;
use Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer;

/**
 * @covers \Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermInspector
 * @uses \Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermErrorBuilder
 * @uses \Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermRestrictions
 * @uses \Toolkit\PhpStan\Rule\Shared\LineOrderedErrors
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\RulePathMatcher
 * @uses \Toolkit\PhpStan\Rule\Shared\Path\RulePathNormalizer
 */
#[CoversClass(ForbiddenFileTermInspector::class)]
#[UsesClass(ForbiddenFileTermErrorBuilder::class)]
#[UsesClass(ForbiddenFileTermRestrictions::class)]
#[UsesClass(LineOrderedErrors::class)]
#[UsesClass(RulePathMatcher::class)]
#[UsesClass(RulePathNormalizer::class)]
final class ForbiddenFileTermInspectorTest extends TestCase
{
    public function testErrorsReturnsEmptyForAnUnmatchedOrUnreadableFile(): void
    {
        $inspector = new ForbiddenFileTermInspector(new ForbiddenFileTermRestrictions([
            'src/Abstract/*' => ['mysql'],
        ]));

        self::assertSame([], $inspector->errors('/project/src/Concrete/Adapter.php'));
        self::assertSame([], $inspector->errors('/project/src/Abstract/Missing.php'));
    }

    public function testErrorsReportsTheSameTermOncePerLineAcrossOverlappingPolicies(): void
    {
        $fixture = __DIR__ . '/../../../../Fixture/ForbidFileTerm/BackendLeak.php';
        $inspector = new ForbiddenFileTermInspector(new ForbiddenFileTermRestrictions([
            'tests/Fixture/ForbidFileTerm/*' => ['mysql'],
            'Fixture/ForbidFileTerm/*' => ['MYSQL'],
        ]));

        self::assertCount(1, $inspector->errors($fixture));
    }
}
