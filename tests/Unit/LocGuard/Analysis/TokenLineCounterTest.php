<?php

declare(strict_types=1);

namespace Tests\Unit\LocGuard\Analysis;

use PhpAiToolkit\LocGuard\Analysis\CodeTokenLineResolver;
use PhpAiToolkit\LocGuard\Analysis\TokenLineCounter;
use PhpToken;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(TokenLineCounter::class)]
#[UsesClass(CodeTokenLineResolver::class)]
final class TokenLineCounterTest extends TestCase
{
    public function testPhysicalLinesCountsStoredLines(): void
    {
        self::assertSame(3, (new TokenLineCounter())->physicalLines("<?php\n\necho 'x';"));
    }

    public function testPhysicalLinesReturnsZeroForEmptySource(): void
    {
        self::assertSame(0, (new TokenLineCounter())->physicalLines(''));
    }

    public function testNonCommentLinesExcludesBlankLinesCommentsAndPhpTags(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

// ignore
echo 'x';

/**
 * ignore
 */
if (true) {
}
PHP, TOKEN_PARSE));

        self::assertSame(3, (new TokenLineCounter())->nonCommentLines($tokens));
    }

    public function testNonCommentLinesCountsCodeWithTrailingCommentsOnlyOnce(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

$value = 1; // ignore
# ignore
/* ignore */
$value++;
PHP, TOKEN_PARSE));

        self::assertSame(2, (new TokenLineCounter())->nonCommentLines($tokens));
    }

    public function testNonCommentLinesCountsHeredocContentAsExecutableTokenLines(): void
    {
        $tokens = array_values(PhpToken::tokenize(<<<'PHP'
<?php

$text = <<<TXT
hello
TXT;
PHP, TOKEN_PARSE));

        self::assertSame(3, (new TokenLineCounter())->nonCommentLines($tokens));
    }
}
