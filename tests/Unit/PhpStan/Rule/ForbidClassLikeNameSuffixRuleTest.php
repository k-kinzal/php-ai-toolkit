<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\ForbidClassLikeNameSuffixRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

/**
 * @extends RuleTestCase<ForbidClassLikeNameSuffixRule>
 */
#[CoversClass(ForbidClassLikeNameSuffixRule::class)]
#[Medium]
final class ForbidClassLikeNameSuffixRuleTest extends RuleTestCase
{
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
                'Class UserHelper uses forbidden suffix "Helper". Rename this class so its name does not end with "Helper"; use a specific domain name instead.',
                7,
            ],
            [
                'Interface PaymentManager uses forbidden suffix "Manager". Rename this interface so its name does not end with "Manager"; use a specific domain name instead.',
                11,
            ],
            [
                'Trait RequestData uses forbidden suffix "Data". Rename this trait so its name does not end with "Data"; use a specific domain name instead.',
                15,
            ],
            [
                'Enum StatusHelper uses forbidden suffix "Helper". Rename this enum so its name does not end with "Helper"; use a specific domain name instead.',
                19,
            ],
        ]);
    }

    public function testProcessNodeAllowedNamesAreNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidClassLikeNameSuffix/WithoutForbiddenSuffixes.php'], []);
    }
}

/**
 * @extends RuleTestCase<ForbidClassLikeNameSuffixRule>
 */
#[CoversClass(ForbidClassLikeNameSuffixRule::class)]
#[Medium]
final class ForbidClassLikeNameSuffixRuleWithoutConfiguredSuffixesTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ForbidClassLikeNameSuffixRule();
    }

    public function testProcessNodeWithoutConfiguredSuffixesDoesNotReport(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidClassLikeNameSuffix/WithForbiddenSuffixes.php'], []);
    }
}
