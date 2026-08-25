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
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPropertyPhpDocErrorCollector;

/**
 * @covers \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiPropertyPhpDocErrorCollector
 * @uses \Toolkit\DocGen\Analysis\Parse\PhpParserBridge
 */
#[CoversClass(PublicApiPropertyPhpDocErrorCollector::class)]
#[UsesClass(PhpParserBridge::class)]
final class PublicApiPropertyPhpDocErrorCollectorTest extends TestCase
{
    /**
     * @dataProvider providerClassWithPublicProperty
     */
    #[DataProvider('providerClassWithPublicProperty')]
    public function testErrorsReturnsPropertyPhpDocError(Class_ $class): void
    {
        $errors = (new PublicApiPropertyPhpDocErrorCollector())->errors($class, 'Example');

        self::assertSame('customRules.requirePhpDocOnProperty', $errors[0]->getIdentifier());
    }

    /**
     * @return array<string, array{Class_}>
     *
     * @throws RuntimeException when the installed parser produces no class
     */
    public static function providerClassWithPublicProperty(): array
    {
        $statements = (new PhpParserBridge())->parser()->parse('<?php class Example { public $name; }');
        $class = $statements[0] ?? null;
        if (!$class instanceof Class_) {
            throw new RuntimeException('The installed parser produced no class from the snippet.');
        }

        return ['public property without PHPDoc' => [$class]];
    }
}
