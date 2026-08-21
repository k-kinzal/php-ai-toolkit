<?php

declare(strict_types=1);

namespace Tests\Unit\Doctest\Execution;

use PhpAiToolkit\Doctest\Analysis\Target;
use PhpAiToolkit\Doctest\DoctestException;
use PhpAiToolkit\Doctest\Execution\SourceLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SourceLoader::class)]
#[UsesClass(Target::class)]
final class SourceLoaderTest extends TestCase
{
    public function testLoadSkipsAFileWhoseClassAnAutoloaderAlreadyResolves(): void
    {
        $target = new Target(Target::CLASS_LIKE, '/does/not/exist.php', '/** */', 'Calculator', 10, 'Tests\Fixture\Doctest\Project');

        (new SourceLoader())->load($target, null);

        self::assertTrue(class_exists('Tests\Fixture\Doctest\Project\Calculator'));
    }

    public function testLoadReadsTheBootstrapFileBeforeTheTarget(): void
    {
        $bootstrap = sys_get_temp_dir() . '/doctest-source-loader-bootstrap.php';
        file_put_contents($bootstrap, "<?php\ndefine('DOCTEST_SOURCE_LOADER_BOOTSTRAP', true);\n");
        $target = new Target(Target::CLASS_LIKE, '/does/not/exist.php', '/** */', 'Calculator', 10, 'Tests\Fixture\Doctest\Project');

        (new SourceLoader())->load($target, $bootstrap);

        self::assertTrue(constant('DOCTEST_SOURCE_LOADER_BOOTSTRAP'));
    }

    public function testLoadFileIncludesAFileOnlyOnce(): void
    {
        $path = sys_get_temp_dir() . '/doctest-source-loader-counter.php';
        file_put_contents($path, "<?php\n\$GLOBALS['doctestSourceLoaderCount'] = (\$GLOBALS['doctestSourceLoaderCount'] ?? 0) + 1;\n");
        $loader = new SourceLoader();

        $loader->loadFile($path);
        $loader->loadFile($path);

        self::assertSame(1, $GLOBALS['doctestSourceLoaderCount']);
    }

    public function testLoadFileRejectsAFileThatDoesNotExist(): void
    {
        $this->expectException(DoctestException::class);
        $this->expectExceptionMessage('Could not load file for doctest execution');

        (new SourceLoader())->loadFile('/does/not/exist.php');
    }

    public function testIsDefinedAnswersPerTargetKind(): void
    {
        $loader = new SourceLoader();
        $namespace = 'Tests\Fixture\Doctest\Project';

        self::assertFalse($loader->isDefined(new Target(Target::FILE, '/a.php', '/** */', 'a.php', 1)));
        self::assertTrue($loader->isDefined(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Calculator', 1, $namespace)));
        self::assertTrue($loader->isDefined(new Target(Target::METHOD, '/a.php', '/** */', 'add', 1, $namespace, 'Calculator')));
        self::assertFalse($loader->isDefined(new Target(Target::CLASS_LIKE, '/a.php', '/** */', 'Missing', 1, $namespace)));
        self::assertFalse($loader->isDefined(new Target(Target::FUNCTION, '/a.php', '/** */', 'missingFunction', 1, $namespace)));
        self::assertTrue($loader->isDefined(new Target(Target::FUNCTION, '/a.php', '/** */', 'strlen', 1)));
    }
}
