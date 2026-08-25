<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Extension\Visibility;

use PHPStan\Analyser\Error;
use PHPStan\Analyser\Scope;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Extension\Visibility\ParserFactoryBridge;
use Toolkit\PhpStan\Extension\Visibility\PublicApiDeclarationLineResolver;
use Toolkit\PhpStan\Extension\Visibility\UnusedErrorIdentifier;
use Toolkit\PhpStan\Extension\Visibility\VisibilityPublicUnusedErrorExtension;
use Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector;

/**
 * @covers \Toolkit\PhpStan\Extension\Visibility\VisibilityPublicUnusedErrorExtension
 * @uses \Toolkit\PhpStan\Extension\Visibility\ParserFactoryBridge
 * @uses \Toolkit\PhpStan\Extension\Visibility\PublicApiDeclarationLineResolver
 * @uses \Toolkit\PhpStan\Extension\Visibility\UnusedErrorIdentifier
 * @uses \Toolkit\PhpStan\Rule\PhpDoc\PublicApi\PublicApiVisibilityDetector
 */
#[CoversClass(VisibilityPublicUnusedErrorExtension::class)]
#[UsesClass(ParserFactoryBridge::class)]
#[UsesClass(PublicApiDeclarationLineResolver::class)]
#[UsesClass(UnusedErrorIdentifier::class)]
#[UsesClass(PublicApiVisibilityDetector::class)]
final class VisibilityPublicUnusedErrorExtensionTest extends TestCase
{
    public function testShouldIgnoreSuppressesUnusedPublicDeclaration(): void
    {
        $file = __DIR__ . '/../../../../Fixture/VisibilityPublicUnused/Declarations.php';
        $error = Error::decode([
            'message' => 'Method is unused.',
            'file' => $file,
            'line' => 33,
            'canBeIgnored' => true,
            'filePath' => $file,
            'traitFilePath' => null,
            'tip' => null,
            'nodeLine' => null,
            'nodeType' => null,
            'identifier' => 'method.unused',
            'metadata' => [],
            'fixedErrorDiffHash' => null,
            'fixedErrorDiffDiff' => null,
        ]);

        self::assertTrue((new VisibilityPublicUnusedErrorExtension())->shouldIgnore(
            $error,
            new \PhpParser\Node\Stmt\Nop(),
            self::createStub(Scope::class),
        ));
    }

    public function testShouldIgnoreKeepsUnusedInternalDeclaration(): void
    {
        $file = __DIR__ . '/../../../../Fixture/VisibilityPublicUnused/Declarations.php';
        $error = Error::decode([
            'message' => 'Method is unused.',
            'file' => $file,
            'line' => 40,
            'canBeIgnored' => true,
            'filePath' => $file,
            'traitFilePath' => null,
            'tip' => null,
            'nodeLine' => null,
            'nodeType' => null,
            'identifier' => 'method.unused',
            'metadata' => [],
            'fixedErrorDiffHash' => null,
            'fixedErrorDiffDiff' => null,
        ]);

        self::assertFalse((new VisibilityPublicUnusedErrorExtension())->shouldIgnore(
            $error,
            new \PhpParser\Node\Stmt\Nop(),
            self::createStub(Scope::class),
        ));
    }

    public function testShouldIgnoreKeepsUnrelatedPublicDeclarationError(): void
    {
        $file = __DIR__ . '/../../../../Fixture/VisibilityPublicUnused/Declarations.php';
        $error = Error::decode([
            'message' => 'Return type is wrong.',
            'file' => $file,
            'line' => 33,
            'canBeIgnored' => true,
            'filePath' => $file,
            'traitFilePath' => null,
            'tip' => null,
            'nodeLine' => null,
            'nodeType' => null,
            'identifier' => 'return.type',
            'metadata' => [],
            'fixedErrorDiffHash' => null,
            'fixedErrorDiffDiff' => null,
        ]);

        self::assertFalse((new VisibilityPublicUnusedErrorExtension())->shouldIgnore(
            $error,
            new \PhpParser\Node\Stmt\Nop(),
            self::createStub(Scope::class),
        ));
    }
}
