<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc;

use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApiClassPhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApiConstantPhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApiMethodPhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApiPhpDocErrorCollector;
use PhpAiToolkit\PhpStan\Rule\PhpDoc\PublicApiPropertyPhpDocErrorCollector;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PublicApiPhpDocErrorCollector::class)]
#[UsesClass(PhpParserBridge::class)]
#[UsesClass(PublicApiClassPhpDocErrorCollector::class)]
#[UsesClass(PublicApiConstantPhpDocErrorCollector::class)]
#[UsesClass(PublicApiMethodPhpDocErrorCollector::class)]
#[UsesClass(PublicApiPropertyPhpDocErrorCollector::class)]
final class PublicApiPhpDocErrorCollectorTest extends TestCase
{
    /**
     * @dataProvider providerUndocumentedClass
     */
    #[DataProvider('providerUndocumentedClass')]
    public function testErrorsReturnsMergedPublicApiErrors(Class_ $class): void
    {
        $errors = (new PublicApiPhpDocErrorCollector())->errors($class, 'Class', 'Example');

        self::assertCount(4, $errors);
    }

    /**
     * @return array<string, array{Class_}>
     *
     * @throws RuntimeException when the installed parser produces no class
     */
    public static function providerUndocumentedClass(): array
    {
        $code = '<?php class Example { public const STATUS = 1; public $name; public function run() {} }';
        $statements = (new PhpParserBridge())->parser()->parse($code);
        $class = $statements[0] ?? null;
        if (!$class instanceof Class_) {
            throw new RuntimeException('The installed parser produced no class from the snippet.');
        }

        return ['class without any PHPDoc' => [$class]];
    }
}
