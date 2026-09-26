<?php

declare(strict_types=1);

namespace Tests\Fixture\DocGen;

use Toolkit\DocGen\Analysis\Model\ClassLikeDoc;
use Toolkit\DocGen\Analysis\Model\DocBlock;
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

final class PublicApiFixture
{
    public static function model(string $root, bool $publicApi): ProjectModel
    {
        $public = new DocBlock('Published API.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['PUBLIC']);
        $restricted = new DocBlock('Implementation.', '', [], null, null, [], [], [], [], [], [], null, false, '', ['namespace']);
        $classes = [];
        $table = new SymbolTable();
        $assignments = [];
        foreach ([
            ['Client', 'Demo\\Api', 'class', 'demo/pkg', $public, false, 'Domain'],
            ['Contract', 'Demo\\Api', 'interface', 'demo/pkg', $public, false, 'Domain'],
            ['Extension', 'Demo\\Api', 'trait', 'demo/pkg', $public, false, 'Domain'],
            ['Status', 'Demo\\Api', 'enum', 'demo/pkg', $public, false, 'Domain'],
            ['Service', 'Demo\\Other', 'class', 'demo/pkg', $public, false, 'Application'],
            ['Helper', 'Demo\\Internal', 'class', 'demo/pkg', $restricted, false, 'Internal'],
            ['Foreign', 'Other', 'class', 'other/pkg', $public, false, 'Domain'],
            ['ApiTest', 'Demo\\Tests', 'class', 'demo/pkg', $public, true, 'Domain'],
        ] as [$name, $namespace, $kind, $package, $doc, $dev, $layer]) {
            $classLike = new ClassLikeDoc($namespace . '\\' . $name, $name, $namespace, $kind, $package, 'src/' . $name . '.php', 1, 2, false, false, [], [], [], [], [], [], [], null, $doc, [], $dev);
            $classes[] = $classLike;
            $table->registerClassLike($classLike);
            $assignments[strtolower($classLike->fqcn)] = [$layer];
        }
        $functions = [
            new FunctionDoc('Demo\\Api\\connect', 'connect', 'Demo\\Api', 'demo/pkg', 'src/connect.php', 1, 2, [], new TypeSignature('\\Demo\\Internal\\Helper', null), $public, [], false),
            new FunctionDoc('Demo\\Internal\\hidden', 'hidden', 'Demo\\Internal', 'demo/pkg', 'src/hidden.php', 1, 2, [], new TypeSignature(null, null), null, [], false),
        ];
        foreach ($functions as $function) {
            $table->registerFunction($function);
        }
        $packages = [
            new DiscoveredPackage(new ComposerManifest($root, 'demo/pkg', 'Demo package', ['Demo\\' => ['src']], [], [], [], []), false),
            new DiscoveredPackage(new ComposerManifest($root, 'other/pkg', 'Other package', ['Other\\' => ['src']], [], [], [], []), false),
        ];

        return new ProjectModel('Demo Docs', $root, $packages, new PackageGraph([]), $classes, $functions, $table, new HierarchyIndex(), new UsageIndex(), new TestCaseIndex(), null, $assignments, null, [], [], null, null, $publicApi);
    }

    public static function writeSources(ProjectModel $model): void
    {
        foreach ($model->classLikes as $classLike) {
            file_put_contents($model->root . '/' . $classLike->file, '<?php // ' . $classLike->fqcn);
        }
        foreach ($model->functions as $function) {
            file_put_contents($model->root . '/' . $function->file, '<?php // ' . $function->fqn);
        }
    }
}
