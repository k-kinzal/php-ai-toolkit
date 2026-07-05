<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use Override;
use PhpAiToolkit\PhpStan\Rule\DescriptivePhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\DescriptivePhpDocTextDetector;
use PhpAiToolkit\PhpStan\Rule\ForbidDescriptivePhpDocInTestClassRule;
use PhpAiToolkit\PhpStan\Rule\RestrictedTestNamespaceMatcher;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\Attributes\UsesClass;

/**
 * @extends RuleTestCase<ForbidDescriptivePhpDocInTestClassRule>
 */
#[CoversClass(ForbidDescriptivePhpDocInTestClassRule::class)]
#[UsesClass(DescriptivePhpDocErrorCollector::class)]
#[UsesClass(DescriptivePhpDocTextDetector::class)]
#[UsesClass(RestrictedTestNamespaceMatcher::class)]
#[Medium]
final class ForbidDescriptivePhpDocInTestClassRuleTest extends RuleTestCase
{
    #[Override]
    protected function getRule(): Rule
    {
        return new ForbidDescriptivePhpDocInTestClassRule();
    }

    public function testGetNodeTypeReturnsExpectedClass(): void
    {
        self::assertSame(\PhpParser\Node\Stmt\ClassLike::class, $this->getRule()->getNodeType());
    }

    public function testProcessNodeDescriptivePhpDocOnTestMethodIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidDescriptivePhpDocInTestClass/WithDescriptivePhpDoc.php'], [
            [
                'Remove descriptive text from PHPDoc on method WithDescriptivePhpDoc::testFooReturnsCorrectValue(). Keep only annotations such as @dataProvider.',
                12,
            ],
        ]);
    }

    public function testProcessNodeDescriptionWithTagIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidDescriptivePhpDocInTestClass/WithDescriptionAndTag.php'], [
            [
                'Remove descriptive text from PHPDoc on method WithDescriptionAndTag::testCalculation(). Keep only annotations such as @dataProvider.',
                14,
            ],
        ]);
    }

    public function testProcessNodeAnnotationOnlyPhpDocIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidDescriptivePhpDocInTestClass/WithAnnotationOnly.php'], []);
    }

    public function testProcessNodeNoPhpDocIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidDescriptivePhpDocInTestClass/WithNoPhpDoc.php'], []);
    }

    public function testProcessNodeNonTestClassIsNotReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidDescriptivePhpDocInTestClass/NonTestClass.php'], []);
    }

    public function testProcessNodeDescriptiveClassDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidDescriptivePhpDocInTestClass/WithDescriptiveClassDoc.php'], [
            [
                'Remove descriptive text from PHPDoc on test class WithDescriptiveClassDoc. Keep only annotations such as @extends.',
                10,
            ],
        ]);
    }

    public function testProcessNodeDescriptiveHelperMethodDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidDescriptivePhpDocInTestClass/WithDescriptiveHelperDoc.php'], [
            [
                'Remove descriptive text from PHPDoc on method WithDescriptiveHelperDoc::setUp(). Keep only annotations such as @dataProvider.',
                12,
            ],
        ]);
    }
}
