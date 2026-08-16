<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\RequirePhpDocOnPublicApi;

use PhpAiToolkit\DocGen\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\PhpStan\Rule\RequirePhpDocOnPublicApi\PublicApiConstantPhpDocErrorCollector;
use PhpParser\Node\Stmt\Class_;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;

#[CoversClass(PublicApiConstantPhpDocErrorCollector::class)]
#[UsesClass(PhpParserBridge::class)]
final class PublicApiConstantPhpDocErrorCollectorTest extends TestCase
{
    /**
     * @dataProvider providerClassWithPublicConstant
     */
    #[DataProvider('providerClassWithPublicConstant')]
    public function testErrorsReturnsConstantPhpDocError(Class_ $class): void
    {
        $errors = (new PublicApiConstantPhpDocErrorCollector())->errors($class, 'Example');

        self::assertSame('customRules.requirePhpDocOnConstant', $errors[0]->getIdentifier());
    }

    /**
     * @return array<string, array{Class_}>
     *
     * @throws RuntimeException when the installed parser produces no class
     */
    public static function providerClassWithPublicConstant(): array
    {
        $statements = (new PhpParserBridge())->parser()->parse('<?php class Example { public const STATUS = 1; }');
        $class = $statements[0] ?? null;
        if (!$class instanceof Class_) {
            throw new RuntimeException('The installed parser produced no class from the snippet.');
        }

        return ['public constant without PHPDoc' => [$class]];
    }
}
