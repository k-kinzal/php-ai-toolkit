<?php

declare(strict_types=1);

namespace Tests\Unit\Document;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Document\PhpConfigReader
 */
#[CoversClass(\Guard\Document\PhpConfigReader::class)]
final class PhpConfigReaderTest extends TestCase
{
    public function testReadExtractsLiteralFixerRules(): void
    {
        $source = <<<'PHP'
<?php
return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR12' => true,
        'array_syntax' => ['syntax' => 'short'],
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
    ]);
PHP;
        self::assertSame([
            'riskyAllowed' => true,
            'rules' => [
                '@PSR12' => true,
                'array_syntax' => ['syntax' => 'short'],
                'ordered_imports' => ['sort_algorithm' => 'alpha'],
            ],
        ], (new \Guard\Document\PhpConfigReader())->read($source));
    }

    public function testArgumentReturnsNullWhenTheCallIsMissing(): void
    {
        self::assertNull((new \Guard\Document\PhpConfigReader())->argument(token_get_all('<?php $x = 1;'), 'setRules'));
    }

    public function testValueReadsAQuotedString(): void
    {
        $tokens = token_get_all('<?php \'alpha\';');
        $parsed = (new \Guard\Document\PhpConfigReader())->value($tokens, 1);
        self::assertSame('alpha', $parsed['value']);
    }

    public function testItemsReadsAList(): void
    {
        $tokens = token_get_all('<?php [1, 2];');
        $parsed = (new \Guard\Document\PhpConfigReader())->items($tokens, 2, ']');
        self::assertSame([1, 2], $parsed['value']);
    }

    public function testEntryReadsAKeyedPair(): void
    {
        $tokens = token_get_all('<?php [\'syntax\' => \'short\'];');
        $parsed = (new \Guard\Document\PhpConfigReader())->entry($tokens, 2);
        self::assertTrue($parsed['keyed']);
        self::assertSame('syntax', $parsed['key']);
        self::assertSame('short', $parsed['value']);
    }

    public function testSkipPassesWhitespace(): void
    {
        $tokens = token_get_all("<?php\n    true;");
        $index = (new \Guard\Document\PhpConfigReader())->skip($tokens, 1);
        self::assertIsArray($tokens[$index]);
        self::assertSame('true', $tokens[$index][1]);
    }

    public function testTokenTextReturnsTheTokenString(): void
    {
        $reader = new \Guard\Document\PhpConfigReader();
        self::assertSame('true', $reader->tokenText([T_STRING, 'true', 1]));
        self::assertSame('', $reader->tokenText('['));
    }

    public function testTextUnquotesSingleQuotes(): void
    {
        self::assertSame("a'b", (new \Guard\Document\PhpConfigReader())->text("'a\\'b'"));
    }

    public function testConstantMapsBooleanLiterals(): void
    {
        $reader = new \Guard\Document\PhpConfigReader();
        self::assertTrue($reader->constant('true'));
        self::assertFalse($reader->constant('FALSE'));
        self::assertNull($reader->constant('SOMETHING'));
    }

    public function testKeyCastsIntegers(): void
    {
        $reader = new \Guard\Document\PhpConfigReader();
        self::assertSame('1', $reader->key(1));
        self::assertSame('', $reader->key(null));
    }
}
