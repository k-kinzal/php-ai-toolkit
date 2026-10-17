<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Enum_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\ClassLikeKindLabel;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\ClassLikeKindLabel
 */
#[CoversClass(ClassLikeKindLabel::class)]
final class ClassLikeKindLabelTest extends TestCase
{
    public function testLabelReturnsClassLikeKind(): void
    {
        $label = new ClassLikeKindLabel();

        self::assertSame('class', $label->label(new Class_('Example')));
        self::assertSame('interface', $label->label(new Interface_('Contract')));
        self::assertSame('trait', $label->label(new Trait_('Behavior')));
        self::assertSame('enum', $label->label(new Enum_('Status')));
    }
}
