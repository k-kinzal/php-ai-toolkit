<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc\PublicApi;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector
 */
#[CoversClass(PublicApiVisibilityDetector::class)]
final class PublicApiVisibilityDetectorTest extends TestCase
{
    public function testDeclaresPublicAcceptsTheTagOnItsOwnCommentLine(): void
    {
        self::assertTrue((new PublicApiVisibilityDetector())->declaresPublic("/**\n * Summary.\n *\n * @visibility public\n */"));
    }

    public function testDeclaresPublicAcceptsTheTagOnASingleLineComment(): void
    {
        self::assertTrue((new PublicApiVisibilityDetector())->declaresPublic('/** @visibility public */'));
    }

    public function testDeclaresPublicRejectsNarrowedScopes(): void
    {
        self::assertFalse((new PublicApiVisibilityDetector())->declaresPublic("/**\n * @visibility namespace\n */"));
        self::assertFalse((new PublicApiVisibilityDetector())->declaresPublic("/**\n * @visibility App\\Billing\n */"));
    }

    public function testDeclaresPublicRejectsAbsentAndProseMentions(): void
    {
        self::assertFalse((new PublicApiVisibilityDetector())->declaresPublic(null));
        self::assertFalse((new PublicApiVisibilityDetector())->declaresPublic("/**\n * Summary.\n */"));
        self::assertFalse((new PublicApiVisibilityDetector())->declaresPublic("/**\n * Write @visibility public to state the intent.\n */"));
    }
}
