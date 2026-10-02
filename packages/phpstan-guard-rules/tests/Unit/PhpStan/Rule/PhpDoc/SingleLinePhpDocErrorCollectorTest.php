<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocDetector;
use Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorBuilder;
use Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorCollector;
use Toolkit\PhpStan\Rule\Shared\AnonymousClassDetector;
use Toolkit\PhpStan\Rule\Shared\CommentTextFormatter;
use Toolkit\PhpStan\Rule\Shared\LineOrderedErrors;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorCollector
 * @uses \Toolkit\PhpStan\Rule\Shared\AnonymousClassDetector
 * @uses \Toolkit\PhpStan\Rule\Shared\CommentTextFormatter
 * @uses \Toolkit\PhpStan\Rule\Shared\LineOrderedErrors
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocDetector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\SingleLinePhpDocErrorBuilder
 */
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
