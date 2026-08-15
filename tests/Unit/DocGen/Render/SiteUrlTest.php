<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PhpAiToolkit\DocGen\Analysis\Model\ClassLikeDoc;
use PhpAiToolkit\DocGen\Analysis\Model\FunctionDoc;
use PhpAiToolkit\DocGen\Analysis\Model\TypeSignature;
use PhpAiToolkit\DocGen\Render\SiteUrl;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SiteUrl::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(TypeSignature::class)]
final class SiteUrlTest extends TestCase
{
    public function testSlugKeepsAllowedCharacters(): void
    {
        self::assertSame('demo/pkg_1.x-y', (new SiteUrl())->slug('demo/pkg_1.x-y'));
    }

    public function testSlugReplacesUnsupportedCharacters(): void
    {
        self::assertSame('demo/pkg-name-2', (new SiteUrl())->slug('demo/pkg name@2'));
    }

    public function testPackagePageAppendsIndexHtml(): void
    {
        self::assertSame('demo/pkg/index.html', (new SiteUrl())->packagePage('demo/pkg'));
    }

    public function testAllItemsPageUsesTheReservedListingFileName(): void
    {
        self::assertSame('demo/pkg/all-items.html', (new SiteUrl())->allItemsPage('demo/pkg'));
    }

    public function testLayerPageSlugsBothPackageAndLayerName(): void
    {
        self::assertSame('demo/pkg/layer.Domain.html', (new SiteUrl())->layerPage('demo/pkg', 'Domain'));
        self::assertSame('demo/pkg/layer.Domain-Core.html', (new SiteUrl())->layerPage('demo/pkg', 'Domain Core'));
    }

    public function testNamespacePageBuildsNestedPath(): void
    {
        self::assertSame('demo/pkg/Demo/Sub/index.html', (new SiteUrl())->namespacePage('demo/pkg', 'Demo\Sub'));
    }

    public function testNamespacePageFallsBackToPackagePageForGlobalNamespace(): void
    {
        self::assertSame('demo/pkg/index.html', (new SiteUrl())->namespacePage('demo/pkg', ''));
    }

    public function testClassLikePagePrefixesKindInNamespaceDirectory(): void
    {
        $classLike = new ClassLikeDoc('Demo\Widget', 'Widget', 'Demo', 'class', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);

        self::assertSame('demo/pkg/Demo/class.Widget.html', (new SiteUrl())->classLikePage($classLike));
    }

    public function testClassLikePageUsesInterfaceKindPrefix(): void
    {
        $classLike = new ClassLikeDoc('Demo\Shape', 'Shape', 'Demo', 'interface', 'demo/pkg', 'src/Shape.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);

        self::assertSame('demo/pkg/Demo/interface.Shape.html', (new SiteUrl())->classLikePage($classLike));
    }

    public function testClassLikePageOmitsDirectoryForGlobalNamespace(): void
    {
        $classLike = new ClassLikeDoc('Widget', 'Widget', '', 'trait', 'demo/pkg', 'src/Widget.php', 3, 20, false, false, [], [], [], [], [], [], [], null, null, [], false);

        self::assertSame('demo/pkg/trait.Widget.html', (new SiteUrl())->classLikePage($classLike));
    }

    public function testFunctionPageUsesFunctionPrefix(): void
    {
        $function = new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/pkg', 'src/functions.php', 3, 5, [], new TypeSignature(null, null), null, [], false);

        self::assertSame('demo/pkg/Demo/function.greet.html', (new SiteUrl())->functionPage($function));
    }

    public function testFunctionPageOmitsDirectoryForGlobalNamespace(): void
    {
        $function = new FunctionDoc('greet', 'greet', '', 'demo/pkg', 'src/functions.php', 3, 5, [], new TypeSignature(null, null), null, [], false);

        self::assertSame('demo/pkg/function.greet.html', (new SiteUrl())->functionPage($function));
    }

    public function testSourcePageAppendsHtmlSuffix(): void
    {
        self::assertSame('src/src/Widget.php.html', (new SiteUrl())->sourcePage('src/Widget.php'));
    }

    public function testDocumentPageNestsThePathUnderThePackageDocDirectory(): void
    {
        self::assertSame('demo/pkg/doc/docs/guide.md.html', (new SiteUrl())->documentPage('demo/pkg', 'docs/guide.md'));
        self::assertSame('demo/pkg/doc/README.md.html', (new SiteUrl())->documentPage('demo/pkg', 'README.md'));
    }

    public function testPrefixRepeatsParentStepPerDirectory(): void
    {
        self::assertSame('../../../', (new SiteUrl())->prefix('demo/pkg/Demo/class.Widget.html'));
    }

    public function testPrefixIsEmptyAtSiteRoot(): void
    {
        self::assertSame('', (new SiteUrl())->prefix('index.html'));
    }

    public function testHrefJoinsPrefixAndTargetPath(): void
    {
        self::assertSame('../../assets/style.css', (new SiteUrl())->href('demo/pkg/index.html', 'assets/style.css'));
    }

    public function testHrefKeepsRootRelativeTargetFromRootPage(): void
    {
        self::assertSame('assets/style.css', (new SiteUrl())->href('index.html', 'assets/style.css'));
    }
}
