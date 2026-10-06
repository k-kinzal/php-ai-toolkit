<?php

declare(strict_types=1);

namespace Tests\Unit\Structure;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\HeadingList
 * @uses \Guard\Structure\Markdown\Heading
 */
#[CoversClass(\Guard\Structure\Markdown\HeadingList::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Markdown\Heading::class)]
final class SubjectTest extends TestCase
{
    /**

     */
    public function testRepresentsParsedContentIndependentlyOfThePolicyUsingIt(): void
    {
        $subject = new \Guard\Structure\Markdown\HeadingList([]);
        self::assertSame([], $subject->all());
    }
}
