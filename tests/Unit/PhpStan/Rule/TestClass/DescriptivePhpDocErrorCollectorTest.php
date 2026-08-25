<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\TestClass;

use PhpAiToolkit\PhpStan\Rule\Shared\Path\RestrictedTestNamespaceMatcher;
use PhpAiToolkit\PhpStan\Rule\TestClass\DescriptivePhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\TestClass\DescriptivePhpDocTextDetector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \PhpAiToolkit\PhpStan\Rule\TestClass\DescriptivePhpDocErrorCollector
 * @uses \PhpAiToolkit\PhpStan\Rule\TestClass\DescriptivePhpDocTextDetector
 * @uses \PhpAiToolkit\PhpStan\Rule\Shared\Path\RestrictedTestNamespaceMatcher
 */
#[CoversClass(DescriptivePhpDocErrorCollector::class)]
#[UsesClass(DescriptivePhpDocTextDetector::class)]
#[UsesClass(RestrictedTestNamespaceMatcher::class)]
final class DescriptivePhpDocErrorCollectorTest extends TestCase
{
    public function testErrorsReturnsDescriptiveClassDocError(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Example');
        $class->namespacedName = new \PhpParser\Node\Name('Tests\Unit\Example');
        $class->setDocComment(new \PhpParser\Comment\Doc("/**\n * Description.\n */", 3));

        $errors = (new DescriptivePhpDocErrorCollector(new RestrictedTestNamespaceMatcher()))->errors($class);

        self::assertCount(1, $errors);
    }
}
