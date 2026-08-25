<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Shared\FileTokenParser;

/**
 * @covers \Toolkit\PhpStan\Rule\Shared\FileTokenParser
 */
#[CoversClass(FileTokenParser::class)]
final class FileTokenParserTest extends TestCase
{
    public function testParseReturnsTokensForReadablePhpFile(): void
    {
        $file = sys_get_temp_dir() . '/file-token-parser-' . uniqid('', true) . '.php';
        file_put_contents($file, '<?php echo "x";');

        try {
            self::assertNotNull((new FileTokenParser())->parse($file));
        } finally {
            unlink($file);
        }
    }

    public function testParseTokenizesInvalidPhpWithoutParsingIt(): void
    {
        $file = sys_get_temp_dir() . '/file-token-parser-' . uniqid('', true) . '.php';
        file_put_contents($file, '<?php function { // still tokenized');

        try {
            $tokens = (new FileTokenParser())->parse($file);

            self::assertNotNull($tokens);
            self::assertContains([T_COMMENT, '// still tokenized', 1], $tokens);
        } finally {
            unlink($file);
        }
    }

    public function testParseReturnsNullForMissingFile(): void
    {
        self::assertNull((new FileTokenParser())->parse(sys_get_temp_dir() . '/missing-' . uniqid('', true) . '.php'));
    }
}
