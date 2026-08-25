<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Render;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\FunctionDoc;
use Toolkit\DocGen\Analysis\Model\TypeSignature;
use Toolkit\DocGen\Analysis\ProjectModel;
use Toolkit\DocGen\Analysis\Reference\HierarchyIndex;
use Toolkit\DocGen\Analysis\Reference\SymbolTable;
use Toolkit\DocGen\Analysis\Reference\TestCaseIndex;
use Toolkit\DocGen\Analysis\Reference\UsageIndex;
use Toolkit\DocGen\Package\ComposerManifest;
use Toolkit\DocGen\Package\DiscoveredPackage;
use Toolkit\DocGen\Package\PackageGraph;
use Toolkit\DocGen\Render\SitePages;

/**
 * @covers \Toolkit\DocGen\Render\SitePages
 * @uses \Toolkit\DocGen\Analysis\Model\ClassLikeDoc
 * @uses \Toolkit\DocGen\Package\ComposerManifest
 * @uses \Toolkit\DocGen\Package\DiscoveredPackage
 * @uses \Toolkit\DocGen\Analysis\Model\FunctionDoc
 * @uses \Toolkit\DocGen\Analysis\Reference\HierarchyIndex
 * @uses \Toolkit\DocGen\Package\PackageGraph
 * @uses \Toolkit\DocGen\Analysis\ProjectModel
 * @uses \Toolkit\DocGen\Analysis\Reference\SymbolTable
 * @uses \Toolkit\DocGen\Analysis\Reference\TestCaseIndex
 * @uses \Toolkit\DocGen\Analysis\Model\TypeSignature
 * @uses \Toolkit\DocGen\Analysis\Reference\UsageIndex
 */
#[CoversClass(SitePages::class)]
#[UsesClass(ClassLikeDoc::class)]
#[UsesClass(ComposerManifest::class)]
#[UsesClass(DiscoveredPackage::class)]
#[UsesClass(FunctionDoc::class)]
#[UsesClass(HierarchyIndex::class)]
#[UsesClass(PackageGraph::class)]
#[UsesClass(ProjectModel::class)]
#[UsesClass(SymbolTable::class)]
#[UsesClass(TestCaseIndex::class)]
#[UsesClass(TypeSignature::class)]
#[UsesClass(UsageIndex::class)]
final class SitePagesTest extends TestCase
{
    public function testNamespacesOfListsSortedNonDevNamespaces(): void
    {
        $acme = new ClassLikeDoc('Acme\A', 'A', 'Acme', 'class', 'demo/pkg', 'src/A.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $demo = new ClassLikeDoc('Demo\B', 'B', 'Demo', 'class', 'demo/pkg', 'src/B.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $dev = new ClassLikeDoc('Devs\C', 'C', 'Devs', 'class', 'demo/pkg', 'tests/C.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], true);
        $other = new ClassLikeDoc('Other\D', 'D', 'Other', 'class', 'other/pkg', 'src/D.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $function = new FunctionDoc('Zeta\greet', 'greet', 'Zeta', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $model = new ProjectModel('T', '/tmp/docgen-root', [], new PackageGraph([]), [$demo, $acme, $dev, $other], [$function], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        self::assertSame(['Acme', 'Demo', 'Zeta'], (new SitePages())->namespacesOf($model, 'demo/pkg'));
    }

    public function testSourceFilesDeduplicatesAndSorts(): void
    {
        $first = new ClassLikeDoc('Demo\A', 'A', 'Demo', 'class', 'demo/pkg', 'src/B.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $second = new ClassLikeDoc('Demo\B', 'B', 'Demo', 'class', 'demo/pkg', 'src/A.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $third = new ClassLikeDoc('Demo\C', 'C', 'Demo', 'class', 'demo/pkg', 'src/B.php', 3, 4, false, false, [], [], [], [], [], [], [], null, null, [], false);
        $dev = new ClassLikeDoc('Demo\D', 'D', 'Demo', 'class', 'demo/pkg', 'tests/C.php', 1, 2, false, false, [], [], [], [], [], [], [], null, null, [], true);
        $function = new FunctionDoc('Demo\greet', 'greet', 'Demo', 'demo/pkg', 'src/fn.php', 1, 2, [], new TypeSignature(null, null), null, [], false);
        $model = new ProjectModel('T', '/tmp/docgen-root', [], new PackageGraph([]), [$first, $second, $third, $dev], [$function], new SymbolTable(), new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, [], null, []);

        self::assertSame(['src/A.php', 'src/B.php', 'src/fn.php', 'tests/C.php'], (new SitePages())->sourceFiles($model));
    }

    public function testReadmeReturnsNullWhenAbsent(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-readme-' . uniqid('', true);
        mkdir($dir, 0777, true);
        $manifest = new ComposerManifest($dir, 'demo/pkg', '', [], [], [], [], []);

        self::assertNull((new SitePages())->readme(new DiscoveredPackage($manifest, false)));
    }

    public function testReadmeReturnsContentsWhenPresent(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-readme-' . uniqid('', true);
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/README.md', "# Demo\n\nHello readme.\n");
        $manifest = new ComposerManifest($dir, 'demo/pkg', '', [], [], [], [], []);

        self::assertSame("# Demo\n\nHello readme.\n", (new SitePages())->readme(new DiscoveredPackage($manifest, false)));
    }

    public function testContentsReadsAFileOrReportsThatItCannot(): void
    {
        $dir = sys_get_temp_dir() . '/docgen-render-read-' . bin2hex(random_bytes(4));
        mkdir($dir, 0777, true);
        file_put_contents($dir . '/file.php', '<?php echo 1;');

        self::assertSame('<?php echo 1;', (new SitePages())->contents($dir . '/file.php'));
        self::assertNull((new SitePages())->contents($dir . '/missing.php'));
        self::assertNull((new SitePages())->contents($dir));
    }
}
