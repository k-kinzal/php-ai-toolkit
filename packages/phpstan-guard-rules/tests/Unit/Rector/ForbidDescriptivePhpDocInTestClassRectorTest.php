<?php

declare(strict_types=1);

namespace Tests\Unit\Rector;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Large;
use PHPUnit\Framework\TestCase;
use Tests\Rector\RectorFixtureRunner;
use Toolkit\Rector\ForbidDescriptivePhpDocInTestClassRector;

/**
 * @covers \Toolkit\Rector\ForbidDescriptivePhpDocInTestClassRector
 * @large
 */
#[CoversClass(ForbidDescriptivePhpDocInTestClassRector::class)]
#[Large]
final class ForbidDescriptivePhpDocInTestClassRectorTest extends TestCase
{
    public function testRefactorPreservesUnsupportedCasesAndIsIdempotent(): void
    {
        $result = (new RectorFixtureRunner())->run('ForbidDescriptivePhpDocInTestClassRector', 'ForbidDescriptivePhpDocInTestClassRector');
        self::assertSame(0, $result['exitCode'], $result['output']);
        self::assertSame($result['expected'], $result['actual']);
        self::assertTrue($result['idempotent'], 'A second Rector run must have no diff.');
    }

    public function testGetNodeTypesRegistersNodesAndRejectsUnrelatedInput(): void
    {
        $rule = new ForbidDescriptivePhpDocInTestClassRector();
        self::assertNotEmpty($rule->getNodeTypes());
        self::assertNull($rule->refactor(new \PhpParser\Node\Expr\Variable('unrelated')));
    }

    public function testRemoveProseRetainsMultilineAnnotationContinuations(): void
    {
        $node = new \PhpParser\Node\Stmt\ClassMethod('testWork');
        $node->setDocComment(new \PhpParser\Comment\Doc("/**\n * Description.\n * @return array{\n *   name: string\n * }\n */"));
        $rule = new ForbidDescriptivePhpDocInTestClassRector();
        self::assertTrue($rule->removeProse($node));
        self::assertSame("/**\n * @return array{\n *   name: string\n * }\n */", $node->getDocComment()?->getText());
        self::assertFalse($rule->removeProse($node));
    }

    public function testConfigureRestrictsChangesToTheSelectedNamespace(): void
    {
        $rule = new ForbidDescriptivePhpDocInTestClassRector();
        $rule->configure(['Project\\Checks']);
        $node = new \PhpParser\Node\Stmt\Class_('Example');
        $node->namespacedName = new \PhpParser\Node\Name('Tests\\Unit\\Example');
        $node->setDocComment(new \PhpParser\Comment\Doc('/** Keep this description. */'));
        self::assertNull($rule->refactor($node));
        $node->namespacedName = new \PhpParser\Node\Name('Project\\Checks\\Example');
        self::assertSame($node, $rule->refactor($node));
        self::assertNull($node->getDocComment());
    }
}
