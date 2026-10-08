<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use Guard\Config\ComponentLoader;
use Guard\Diagnostic\PolicyException;
use Guard\Policy\FieldConstraints;
use Guard\Structure\Source;
use Guard\Structure\Structurer;
use Guard\Structure\Text;
use Guard\Structure\TextStructurer;
use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Config\ComponentLoader
 * @uses \Guard\Policy\FieldConstraints
 * @uses \Guard\Diagnostic\PolicyException
 * @uses \Guard\Structure\Source
 * @uses \Guard\Structure\Text
 * @uses \Guard\Structure\TextStructurer
 */
#[CoversClass(ComponentLoader::class)]
#[UsesClass(FieldConstraints::class)]
#[UsesClass(PolicyException::class)]
#[UsesClass(Source::class)]
#[UsesClass(Text::class)]
#[UsesClass(TextStructurer::class)]
final class ComponentLoaderTest extends TestCase
{
    public function testCreateLoadsAnOrdinaryPolicyWithNamedConstructorArguments(): void
    {
        self::assertInstanceOf(FieldConstraints::class, (new ComponentLoader())->create(FieldConstraints::class, ['rules' => []]));
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testCreateLoadsAnOrdinaryStructurerWithoutOptions(): void
    {
        $structurer = (new ComponentLoader())->create(TextStructurer::class, []);
        self::assertInstanceOf(TextStructurer::class, $structurer);
        self::assertSame('source', $structurer->structure(new Source('source', []))->content());
    }

    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testNamedArgumentsKeepDefaultsAndDoNotDependOnYamlKeyOrder(): void
    {
        $class = get_class(new class () implements Structurer {
            public function __construct(private string $prefix = 'before:', private string $suffix = ':after')
            {
            }
            public function structure(Source $source): Text
            {
                return new Text($this->prefix . $source->text() . $this->suffix);
            }
        });
        $structurer = (new ComponentLoader())->create($class, ['suffix' => '!']);
        self::assertInstanceOf(Structurer::class, $structurer);
        $subject = $structurer->structure(new Source('source', []));
        self::assertInstanceOf(Text::class, $subject);
        self::assertSame('before:source!', $subject->content());
    }

    /**
     * @dataProvider providerInvalidComponents
     * @param array<string, mixed> $options
     */
    #[DataProvider('providerInvalidComponents')]
    public function testCreateIdentifiesInvalidClassesAndArguments(string $class, array $options, string $message): void
    {
        $this->expectException(PolicyException::class);
        $this->expectExceptionMessage('Class "' . $class . '"');
        $this->expectExceptionMessage($message);
        (new ComponentLoader())->create($class, $options);
    }

    /**
     * @return iterable<string, array{string, array<string, mixed>, string}>
     */
    public static function providerInvalidComponents(): iterable
    {
        yield 'missing' => ['Missing\\Policy', [], 'composer dump-autoload'];
        yield 'wrong contract' => [self::class, [], 'must implement Guard\\Policy\\Policy or Guard\\Structure\\Structurer'];
        yield 'required argument' => [FieldConstraints::class, [], 'Check its named constructor arguments'];
        yield 'unknown argument' => [FieldConstraints::class, ['typo' => true], 'Check its named constructor arguments'];
        yield 'wrong argument type' => [FieldConstraints::class, ['rules' => true], 'Check its named constructor arguments'];
        yield 'no constructor' => [TextStructurer::class, ['typo' => true], 'Remove its options'];
    }
}
