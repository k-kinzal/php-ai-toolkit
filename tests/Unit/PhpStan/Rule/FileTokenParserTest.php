<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule;

use PhpAiToolkit\PhpStan\Rule\FileTokenParser;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FileTokenParser::class)]
final class FileTokenParserTest extends TestCase
{
    public function testParseReturnsTokensForReadablePhpFile(): void
    {
        $file = sys_get_temp_dir() . '/file-token-parser-' . bin2hex(random_bytes(4)) . '.php';
        file_put_contents($file, '<?php echo "x";');

        try {
            self::assertNotNull((new FileTokenParser())->parse($file));
        } finally {
            unlink($file);
        }
    }

    public function testParseReturnsNullForMissingFile(): void
    {
        self::assertNull((new FileTokenParser())->parse(sys_get_temp_dir() . '/missing-' . bin2hex(random_bytes(4)) . '.php'));
    }
}
