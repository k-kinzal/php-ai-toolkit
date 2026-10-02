<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse\Builder;

use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Doc\DocBlockReader;
use Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use Toolkit\DocGen\Analysis\Model\MethodDoc;
use Toolkit\DocGen\Analysis\Model\ParameterDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\Parse\AstParser;
use Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder;
use Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder;
use Toolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use Toolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use Toolkit\DocGen\Analysis\Parse\ParameterModifiers;
use Toolkit\DocGen\Analysis\Parse\PhpParserBridge;

/**
 * @covers \Toolkit\DocGen\Analysis\Parse\Builder\MethodBuilder
 * @uses \Toolkit\DocGen\Analysis\Parse\AstParser
 * @uses \Toolkit\DocGen\Analysis\Doc\DocBlockReader
 * @uses \Toolkit\DocGen\Analysis\Parse\ExprTextPrinter
 * @uses \Toolkit\DocGen\Analysis\Model\MethodDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\NativeTypePrinter
 * @uses \Toolkit\DocGen\Analysis\Parse\Builder\ParameterBuilder
 * @uses \Toolkit\DocGen\Analysis\Model\ParameterDoc
 * @uses \Toolkit\DocGen\Analysis\Parse\ParameterModifiers
 * @uses \Toolkit\DocGen\Analysis\Doc\PhpDocParserBridge
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 */
#[CoversClass(MethodBuilder::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(DocBlockReader::class)]
#[UsesClass(ExprTextPrinter::class)]
#[UsesClass(MethodDoc::class)]
#[UsesClass(NativeTypePrinter::class)]
#[UsesClass(ParameterBuilder::class)]
#[UsesClass(ParameterDoc::class)]
#[UsesClass(ParameterModifiers::class)]
#[UsesClass(PhpDocParserBridge::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(TypeSignature::class)]
final class MethodBuilderTest extends TestCase
{
    public function testBuildReadsAbstractProtectedMethod(): void
    {
        $code = <<<'PHP'
<?php

abstract class Sample
{
    abstract protected function resolve(): void;
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $method = $statement->getMethod('resolve');
        self::assertNotNull($method);

        $doc = (new MethodBuilder())->build($method);

        self::assertSame('resolve', $doc->name);
        self::assertSame('protected', $doc->visibility);
        self::assertFalse($doc->isStatic);
        self::assertTrue($doc->isAbstract);
        self::assertFalse($doc->isFinal);
        self::assertSame([], $doc->parameters);
        self::assertSame('void', $doc->returnType->native);
        self::assertNull($doc->returnType->annotated);
        self::assertNull($doc->docBlock);
        self::assertSame(5, $doc->startLine);
        self::assertSame(5, $doc->endLine);
    }

    public function testBuildReadsFinalStaticMethodWithParameters(): void
    {
        $code = <<<'PHP'
<?php

class Sample
{
    final public static function make(int $count = 3): self
    {
        return new self();
    }
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $method = $statement->getMethod('make');
        self::assertNotNull($method);

        $doc = (new MethodBuilder())->build($method);

        self::assertSame('make', $doc->name);
        self::assertSame('public', $doc->visibility);
        self::assertTrue($doc->isStatic);
        self::assertFalse($doc->isAbstract);
        self::assertTrue($doc->isFinal);
        self::assertCount(1, $doc->parameters);
        self::assertSame('count', $doc->parameters[0]->name);
        self::assertSame('int', $doc->parameters[0]->type->native);
        self::assertSame('3', $doc->parameters[0]->defaultText);
        self::assertSame('self', $doc->returnType->native);
        self::assertSame(5, $doc->startLine);
        self::assertSame(8, $doc->endLine);
    }

    public function testBuildReadsPrivateVisibility(): void
    {
        $code = <<<'PHP'
<?php

class Sample
{
    private function hidden(): void
    {
    }
}
PHP;
        $statement = (new AstParser())->parse($code, 'sample.php')[0];
        self::assertInstanceOf(Class_::class, $statement);
        $method = $statement->getMethod('hidden');
        self::assertNotNull($method);

        $doc = (new MethodBuilder())->build($method);

        self::assertSame('private', $doc->visibility);
    }
}
