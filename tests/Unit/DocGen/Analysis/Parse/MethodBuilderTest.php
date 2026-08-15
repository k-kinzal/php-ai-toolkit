<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Parse;

use PhpAiToolkit\DocGen\Analysis\Doc\DocBlockReader;
use PhpAiToolkit\DocGen\Analysis\Doc\PhpDocParserBridge;
use PhpAiToolkit\DocGen\Analysis\Model\MethodDoc;
use PhpAiToolkit\DocGen\Analysis\Model\ParameterDoc;
use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\ExprTextPrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\MethodBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\NativeTypePrinter;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterBuilder;
use PhpAiToolkit\DocGen\Analysis\Parse\ParameterModifiers;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

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
