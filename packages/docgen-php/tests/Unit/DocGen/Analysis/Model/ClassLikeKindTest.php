<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Model\ClassLikeKind;

/**
 * @coversNothing
 */
#[CoversNothing]
final class ClassLikeKindTest extends TestCase
{
    public function testConstantValues(): void
    {
        self::assertSame('class', ClassLikeKind::CLASS_);
        self::assertSame('interface', ClassLikeKind::INTERFACE_);
        self::assertSame('trait', ClassLikeKind::TRAIT_);
        self::assertSame('enum', ClassLikeKind::ENUM_);
    }
}
