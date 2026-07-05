<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\DescriptivePhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\DescriptivePhpDocTextDetector;
use PhpAiToolkit\PhpStan\Rule\RestrictedTestNamespaceMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
