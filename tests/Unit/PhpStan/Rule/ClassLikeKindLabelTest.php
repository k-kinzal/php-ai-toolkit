<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\ClassLikeKindLabel;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ClassLikeKindLabel::class)]
final class ClassLikeKindLabelTest extends TestCase
{
    public function testLabelReturnsClassLikeKind(): void
    {
        $label = new ClassLikeKindLabel();

        self::assertSame('class', $label->label(new \PhpParser\Node\Stmt\Class_('Example')));
        self::assertSame('interface', $label->label(new \PhpParser\Node\Stmt\Interface_('Contract')));
        self::assertSame('trait', $label->label(new \PhpParser\Node\Stmt\Trait_('Behavior')));
        self::assertSame('enum', $label->label(new \PhpParser\Node\Stmt\Enum_('Status')));
    }
}
