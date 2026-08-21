<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocDetector;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorBuilder;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\Shared\AnonymousClassDetector;
use PhpAiToolkit\PhpStan\Rule\Shared\CommentTextFormatter;
use PhpAiToolkit\PhpStan\Rule\Shared\LineOrderedErrors;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SingleLinePhpDocErrorCollector::class)]
#[UsesClass(AnonymousClassDetector::class)]
#[UsesClass(CommentTextFormatter::class)]
#[UsesClass(LineOrderedErrors::class)]
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
