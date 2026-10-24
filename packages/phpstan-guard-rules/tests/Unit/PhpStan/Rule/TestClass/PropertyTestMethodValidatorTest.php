<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpParser\Comment\Doc;
use PhpParser\Node\Attribute;
use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassMethod;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\TestClass\PropertyTestMethodValidator;

/**
 * @covers \Toolkit\PhpStan\Rule\TestClass\PropertyTestMethodValidator
 */
#[CoversClass(PropertyTestMethodValidator::class)]
final class PropertyTestMethodValidatorTest extends TestCase
{
    public function testErrorsRejectsHelpersEvenWithTestMetadata(): void
    {
        $method = new ClassMethod('helper');
        $method->setDocComment(new Doc('/** @test */'));
        $errors = (new PropertyTestMethodValidator())->errors($method, 'CollectionProperties');
        self::assertSame('customRules.propertyTestMethodName', $errors[0]->getIdentifier());
    }

    public function testHasTestMetadataAcceptsLegacyAnnotation(): void
    {
        $method = new ClassMethod('satisfiesIdentity');
        $method->setDocComment(new Doc('/** @test */'));
        self::assertTrue((new PropertyTestMethodValidator(false))->hasTestMetadata($method));
    }

    public function testHasTestMetadataRejectsUnrelatedAttributeAndLongerAnnotation(): void
    {
        $method = new ClassMethod('satisfiesIdentity', ['attrGroups' => [new AttributeGroup([new Attribute(new Name('Example\\Test'))])]]);
        $method->setDocComment(new Doc('/** @testWith [1] */'));
        self::assertFalse((new PropertyTestMethodValidator())->hasTestMetadata($method));
    }

    public function testHasTestMetadataRequiresAttributeOnModernPhpUnit(): void
    {
        $method = new ClassMethod('satisfiesIdentity');
        $method->setDocComment(new Doc('/** @test */'));
        self::assertFalse((new PropertyTestMethodValidator(true))->hasTestMetadata($method));
        $method->attrGroups = [new AttributeGroup([new Attribute(new Name('PHPUnit\\Framework\\Attributes\\Test'))])];
        self::assertTrue((new PropertyTestMethodValidator(true))->hasTestMetadata($method));
        $method->setAttribute('comments', []);
        self::assertFalse((new PropertyTestMethodValidator(false))->hasTestMetadata($method));
    }
}
