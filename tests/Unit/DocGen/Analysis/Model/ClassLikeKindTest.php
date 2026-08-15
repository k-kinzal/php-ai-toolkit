<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Model;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeKind;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassLikeKind::class)]
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
