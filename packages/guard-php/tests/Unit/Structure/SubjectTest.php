<?php

declare(strict_types=1);

namespace Tests\Unit\Structure;

use Guard\Structure\Markdown\Heading;
use Guard\Structure\Markdown\HeadingList;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Markdown\HeadingList
 * @uses \Guard\Structure\Markdown\Heading
 */
#[CoversClass(HeadingList::class)]
#[UsesClass(Heading::class)]
final class SubjectTest extends TestCase
{
    /**

     */
    public function testRepresentsParsedContentIndependentlyOfThePolicyUsingIt(): void
    {
        $subject = new HeadingList([]);
        self::assertSame([], $subject->all());
    }
}
