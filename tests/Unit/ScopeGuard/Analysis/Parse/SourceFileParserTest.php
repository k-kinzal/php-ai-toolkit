<?php

declare(strict_types=1);

namespace Tests\Unit\ScopeGuard\Analysis\Parse;

use PhpAiToolkit\ScopeGuard\Analysis\Parse\PhpParserBridge;
use PhpAiToolkit\ScopeGuard\Analysis\Parse\SourceFileParser;
use PhpAiToolkit\ScopeGuard\ScopeGuardException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourceFileParser::class)]
#[UsesClass(PhpParserBridge::class)]
final class SourceFileParserTest extends TestCase
{
    /**
     * @throws ScopeGuardException
     */
    public function testParseResolvesImportedNamesToFullyQualifiedOnes(): void
    {
        $path = sys_get_temp_dir() . '/scopeguard-parse-' . uniqid('', true) . '.php';
        file_put_contents($path, "<?php\nnamespace App\\Http;\nuse App\\Domain\\Order;\nfinal class Controller extends Order {}\n");

        $statements = (new SourceFileParser())->parse($path);
        unlink($path);
        $namespace = $statements[0];
        self::assertInstanceOf(\PhpParser\Node\Stmt\Namespace_::class, $namespace);
        $class = $namespace->stmts[1];
        self::assertInstanceOf(\PhpParser\Node\Stmt\Class_::class, $class);

        self::assertSame('App\\Domain\\Order', $class->extends?->toString());
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseRejectsAMissingFile(): void
    {
        $this->expectException(ScopeGuardException::class);

        (new SourceFileParser())->parse(sys_get_temp_dir() . '/scopeguard-missing-' . uniqid('', true) . '.php');
    }

    /**
     * @throws ScopeGuardException
     */
    public function testParseRejectsUnparsableSource(): void
    {
        $path = sys_get_temp_dir() . '/scopeguard-broken-' . uniqid('', true) . '.php';
        file_put_contents($path, "<?php\nfinal class {{{\n");

        $this->expectException(ScopeGuardException::class);

        (new SourceFileParser())->parse($path);
    }
}
