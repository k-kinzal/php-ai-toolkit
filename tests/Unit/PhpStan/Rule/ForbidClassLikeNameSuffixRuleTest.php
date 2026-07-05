<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\ClassLikeKindLabel;
use PhpAiToolkit\PhpStan\Rule\ForbidClassLikeNameSuffixRule;
use PhpAiToolkit\PhpStan\Rule\ForbiddenClassLikeSuffixes;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<ForbidClassLikeNameSuffixRule>
 */
#[CoversClass(ForbidClassLikeNameSuffixRule::class)]
#[CoversClass(ClassLikeKindLabel::class)]
#[CoversClass(ForbiddenClassLikeSuffixes::class)]
#[Medium]
final class ForbidClassLikeNameSuffixRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidClassLikeNameSuffixRule(['Helper', 'Manager', 'Data']);
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassLike::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeForbiddenSuffixesAreReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidClassLikeNameSuffix/WithForbiddenSuffixes.php'], [
            [
                'Rename class UserHelper to a specific domain name without the "Helper" suffix.',
                7,
            ],
            [
                'Rename interface PaymentManager to a specific domain name without the "Manager" suffix.',
                11,
            ],
            [
                'Rename trait RequestData to a specific domain name without the "Data" suffix.',
                15,
            ],
            [
                'Rename enum StatusHelper to a specific domain name without the "Helper" suffix.',
                19,
            ],
        ]);
    }

    public function testProcessNodeAllowedNamesAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidClassLikeNameSuffix/WithoutForbiddenSuffixes.php'], []);
    }

    public function testMatchingSuffixReturnsConfiguredSuffix(): void
    {
        self::assertSame('Helper', (new ForbiddenClassLikeSuffixes(['Helper']))->matchingSuffix('UserHelper'));
        self::assertNull((new ForbiddenClassLikeSuffixes(['Helper']))->matchingSuffix('UserProfile'));
    }

    public function testLabelReturnsClassLikeKind(): void
    {
        $label = new ClassLikeKindLabel();

        self::assertSame('class', $label->label(new \PhpParser\Node\Stmt\Class_('Example')));
        self::assertSame('interface', $label->label(new \PhpParser\Node\Stmt\Interface_('Contract')));
        self::assertSame('trait', $label->label(new \PhpParser\Node\Stmt\Trait_('Behavior')));
        self::assertSame('enum', $label->label(new \PhpParser\Node\Stmt\Enum_('Status')));
    }
}

/**
 * @extends RuleTestCase<ForbidClassLikeNameSuffixRule>
 */
#[CoversClass(ForbidClassLikeNameSuffixRule::class)]
#[Medium]
final class ForbidClassLikeNameSuffixRuleWithoutConfiguredSuffixesTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidClassLikeNameSuffixRule();
    }

    public function testProcessNodeWithoutConfiguredSuffixesDoesNotReport(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidClassLikeNameSuffix/WithForbiddenSuffixes.php'], []);
    }
}
