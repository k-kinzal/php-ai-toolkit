<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Support;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Support\NonDocCommentCatchContext;

/**
 * @covers \Toolkit\PhpStan\Support\NonDocCommentCatchContext
 */
#[CoversClass(NonDocCommentCatchContext::class)]
final class NonDocCommentCatchContextTest extends TestCase
{
    public function testRegisterCatchAllowsLineCommentAfterBodyOpens(): void
    {
        $context = new NonDocCommentCatchContext();
        $context->registerCatch();
        $context->registerStringToken('{');

        self::assertTrue($context->allowsLineComment());
    }

    public function testCancelPendingCatchPreventsCatchBodyTracking(): void
    {
        $context = new NonDocCommentCatchContext();
        $context->registerCatch();
        $context->cancelPendingCatch();
        $context->registerStringToken('{');

        self::assertFalse($context->allowsLineComment());
    }

    public function testRegisterStringTokenClosesCatchBody(): void
    {
        $context = new NonDocCommentCatchContext();
        $context->registerCatch();
        $context->registerStringToken('{');
        $context->registerStringToken('}');

        self::assertFalse($context->allowsLineComment());
    }

    public function testAllowsLineCommentReturnsFalseOutsideCatchBody(): void
    {
        self::assertFalse((new NonDocCommentCatchContext())->allowsLineComment());
    }
}
