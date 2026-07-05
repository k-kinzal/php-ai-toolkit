<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\AnonymousClassDetector;
use PhpAiToolkit\PhpStan\Rule\CommentTextFormatter;
use PhpAiToolkit\PhpStan\Rule\SingleLinePhpDocDetector;
use PhpAiToolkit\PhpStan\Rule\SingleLinePhpDocErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\SingleLinePhpDocErrorCollector;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SingleLinePhpDocErrorCollector::class)]
#[UsesClass(AnonymousClassDetector::class)]
#[UsesClass(CommentTextFormatter::class)]
#[UsesClass(SingleLinePhpDocDetector::class)]
#[UsesClass(SingleLinePhpDocErrorBuilder::class)]
final class SingleLinePhpDocErrorCollectorTest extends TestCase
{
    public function testErrorsReturnsClassDocError(): void
    {
        $class = new \PhpParser\Node\Stmt\Class_('Example');
        $class->setDocComment(new \PhpParser\Comment\Doc('/** doc */', 3));

        $errors = (new SingleLinePhpDocErrorCollector())->errors($class, self::createStub(Scope::class));

        self::assertCount(1, $errors);
    }
}
