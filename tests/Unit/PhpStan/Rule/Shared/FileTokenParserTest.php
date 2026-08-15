<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Shared;

use PhpAiToolkit\PhpStan\Rule\Shared\FileTokenParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

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

    public function testParseReturnsNullForMissingFile(): void
    {
        self::assertNull((new FileTokenParser())->parse(sys_get_temp_dir() . '/missing-' . uniqid('', true) . '.php'));
    }
}
