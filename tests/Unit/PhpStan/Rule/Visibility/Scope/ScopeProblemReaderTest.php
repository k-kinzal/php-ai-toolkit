<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Visibility\Scope;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage;
use Toolkit\PhpStan\Rule\Visibility\Scope\ScopeProblemReader;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScopeResolver;
use Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityTagParser;

/**
 * @covers \Toolkit\PhpStan\Rule\Visibility\Scope\ScopeProblemReader
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\NamespaceLineage
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityScopeResolver
 * @uses \Toolkit\PhpStan\Rule\Visibility\Scope\VisibilityTagParser
 */
#[CoversClass(ScopeProblemReader::class)]
#[UsesClass(NamespaceLineage::class)]
#[UsesClass(VisibilityScopeResolver::class)]
#[UsesClass(VisibilityTagParser::class)]
final class ScopeProblemReaderTest extends TestCase
{
    public function testProblemExplainsMistypedKeyword(): void
    {
        $problem = (new ScopeProblemReader())->problem('parrent', 'App\Domain');
        self::assertIsString($problem);
        self::assertStringContainsString(
            '"parrent" is not one of',
            $problem,
        );
    }

    public function testProblemAcceptsUsableScope(): void
    {
        self::assertNull((new ScopeProblemReader())->problem('parent', 'App\Domain'));
    }

    public function testParentProblemRejectsRootNamespace(): void
    {
        self::assertNotNull((new ScopeProblemReader())->parentProblem('App'));
    }

    public function testNamespaceProblemAcceptsQualifiedNamespace(): void
    {
        self::assertNull((new ScopeProblemReader())->namespaceProblem('App\Domain'));
    }
}
