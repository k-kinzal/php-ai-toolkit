<?php

declare(strict_types=1);

namespace Tests\Unit\Cli;

use PhpStanAiRules\Cli\RelativePathResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(RelativePathResolver::class)]
final class RelativePathResolverTest extends TestCase
{
    /**
     * @return array<string, array{string, string, string}>
     */
    public static function providerRelativePath(): array
    {
        return [
            'sibling directories' => [
                '/a/b',
                '/a/c',
                '../c',
            ],
            'child directory' => [
                '/a',
                '/a/b/c',
                'b/c',
            ],
            'parent directory' => [
                '/a/b/c',
                '/a',
                '../..',
            ],
            'same directory' => [
                '/a/b',
                '/a/b',
                '',
            ],
            'deep nesting' => [
                '/a/b/c/d',
                '/a/x/y',
                '../../../x/y',
            ],
            'no common prefix' => [
                '/a/b',
                '/x/y',
                '../../x/y',
            ],
            'real-world symlink case' => [
                '/project/.claude/skills',
                '/project/vendor/k-kinzal/php-ai-toolkit/skills/my-skill',
                '../../vendor/k-kinzal/php-ai-toolkit/skills/my-skill',
            ],
        ];
    }

    #[DataProvider('providerRelativePath')]
    public function testRelativePath(string $from, string $to, string $expected): void
    {
        self::assertSame($expected, RelativePathResolver::relativePath($from, $to));
    }
}
