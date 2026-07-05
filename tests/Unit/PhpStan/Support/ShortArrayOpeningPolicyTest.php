<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Support;

use PhpAiToolkit\PhpStan\Support\NonDocCommentTokenClassifier;
use PhpAiToolkit\PhpStan\Support\ShortArrayOpeningPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShortArrayOpeningPolicy::class)]
#[UsesClass(NonDocCommentTokenClassifier::class)]
final class ShortArrayOpeningPolicyTest extends TestCase
{
    public function testIsOpeningRejectsExpressionEndTokens(): void
    {
        $policy = new ShortArrayOpeningPolicy();

        self::assertTrue($policy->isOpening('='));
        self::assertFalse($policy->isOpening([T_VARIABLE, '$items']));
        self::assertFalse($policy->isOpening(']'));
    }
}
