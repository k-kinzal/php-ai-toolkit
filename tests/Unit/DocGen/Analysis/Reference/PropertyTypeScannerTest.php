<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Analysis\Reference;

use PhpAiToolkit\DocGen\Analysis\Parse\AstParser;
use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\DocGen\Analysis\Reference\PropertyTypeScanner;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassLike;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PropertyTypeScanner::class)]
#[UsesClass(AstParser::class)]
#[UsesClass(PhpParserBridge::class)]
final class PropertyTypeScannerTest extends TestCase
{
    public function testScanCollectsTypedPropertiesKeyedByLowercasedName(): void
    {
        $code = <<<'PHP'
<?php

class Holder
{
    private \Demo\Greeter $Service;

    private \Demo\Logger $first, $second;
}
PHP;
        $classes = array_values(array_filter(
            (new AstParser())->parse($code, 'holder.php'),
            static fn (Stmt $statement): bool => $statement instanceof ClassLike,
        ));

        self::assertCount(1, $classes);
        self::assertSame(
            ['service' => 'Demo\Greeter', 'first' => 'Demo\Logger', 'second' => 'Demo\Logger'],
            (new PropertyTypeScanner())->scan($classes[0]),
        );
    }

    public function testScanCollectsPromotedConstructorParameterClassTypesOnly(): void
    {
        $code = <<<'PHP'
<?php

class Holder
{
    public function __construct(private \Demo\Greeter $greeter, \Demo\Logger $plain, private string $count)
    {
    }
}
PHP;
        $classes = array_values(array_filter(
            (new AstParser())->parse($code, 'holder.php'),
            static fn (Stmt $statement): bool => $statement instanceof ClassLike,
        ));

        self::assertCount(1, $classes);
        self::assertSame(['greeter' => 'Demo\Greeter'], (new PropertyTypeScanner())->scan($classes[0]));
    }

    public function testScanIgnoresUnionAndBuiltinPropertyTypes(): void
    {
        $code = <<<'PHP'
<?php

class Holder
{
    private \Demo\Greeter|\Demo\Logger $either;

    private string $name;

    private $untyped;
}
PHP;
        $classes = array_values(array_filter(
            (new AstParser())->parse($code, 'holder.php'),
            static fn (Stmt $statement): bool => $statement instanceof ClassLike,
        ));

        self::assertCount(1, $classes);
        self::assertSame([], (new PropertyTypeScanner())->scan($classes[0]));
    }
}
