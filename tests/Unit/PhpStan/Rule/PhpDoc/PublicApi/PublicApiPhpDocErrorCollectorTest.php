<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\PhpDoc\PublicApi;

use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Toolkit\DocGen\Analysis\Parse\PhpParserBridge;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiClassPhpDocErrorCollector;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiConstantPhpDocErrorCollector;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiMethodPhpDocErrorCollector;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPhpDocErrorCollector;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPropertyPhpDocErrorCollector;
use Toolkit\PhpStan\Rule\Shared\LineOrderedErrors;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPhpDocErrorCollector
 * @uses \Toolkit\PhpStan\Rule\Shared\LineOrderedErrors
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiClassPhpDocErrorCollector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiConstantPhpDocErrorCollector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiMethodPhpDocErrorCollector
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPropertyPhpDocErrorCollector
 */
#[CoversClass(PublicApiPhpDocErrorCollector::class)]
#[UsesClass(LineOrderedErrors::class)]
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
