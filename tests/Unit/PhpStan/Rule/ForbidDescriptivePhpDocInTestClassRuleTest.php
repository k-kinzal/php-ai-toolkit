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
                'Method WithDescriptivePhpDoc::testFooReturnsCorrectValue() has descriptive PHPDoc text. Remove the description. Annotation-only PHPDoc (e.g., @dataProvider) is allowed.',
                12,
            ],
        ]);
    }

    public function testProcessNodeDescriptionWithTagIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidDescriptivePhpDocInTestClass/WithDescriptionAndTag.php'], [
            [
                'Method WithDescriptionAndTag::testCalculation() has descriptive PHPDoc text. Remove the description. Annotation-only PHPDoc (e.g., @dataProvider) is allowed.',
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
                'Test class WithDescriptiveClassDoc has descriptive PHPDoc text. Remove the description. Annotation-only PHPDoc (e.g., @extends) is allowed.',
                10,
            ],
        ]);
    }

    public function testProcessNodeDescriptiveHelperMethodDocIsReported(): void
    {
        $this->analyse([__DIR__ . '/../../../Fixture/ForbidDescriptivePhpDocInTestClass/WithDescriptiveHelperDoc.php'], [
            [
                'Method WithDescriptiveHelperDoc::setUp() has descriptive PHPDoc text. Remove the description. Annotation-only PHPDoc (e.g., @dataProvider) is allowed.',
                12,
            ],
        ]);
    }
}
