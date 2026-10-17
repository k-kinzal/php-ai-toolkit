<?php

declare(strict_types=1);

namespace Tests\Integration;

use Guard\Cli\Application;
use Guard\Structure\Document\DataDocument;
use Guard\Structure\Document\PhpDocument;
use Guard\Structure\Document\XmlDocument;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Medium;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @coversNothing
 * @medium
 */
#[CoversNothing]
#[Medium]
final class ConfigurationRepairTest extends TestCase
{
    /**
     * @dataProvider providerFormats
     */
    #[DataProvider('providerFormats')]
    public function testCheckAndFixUseTheSameFieldAndPreserveIndependentSettings(string $format, string $source, string $selector, string $custom, bool|string $value): void
    {
        $root = sys_get_temp_dir() . '/guard-repair-' . uniqid('', true);
        mkdir($root);
        $file = 'settings.' . $format;
        file_put_contents($root . '/' . $file, $source);
        file_put_contents($root . '/guard.yaml', Yaml::dump(['version' => 1, 'configuration' => [
            ['id' => 'feature.enabled', 'file' => $file, 'format' => $format, 'select' => $selector, 'assert' => ['equals' => $value]],
        ]], 8));
        $application = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(1, $application->run(['check']));
            self::assertSame(0, $application->run(['fix', '--dry-run']));
            self::assertSame($source, file_get_contents($root . '/' . $file));
            self::assertSame(0, $application->run(['fix']));
            self::assertSame(0, $application->run(['check']));
            $fixed = file_get_contents($root . '/' . $file);
            self::assertIsString($fixed);
            $document = match ($format) {
                'xml' => new XmlDocument($fixed),
                'php' => new PhpDocument($fixed),
                default => new DataDocument($format, $fixed),
            };
            self::assertSame($value, $document->read($selector)->value);
            self::assertSame($value, $document->read($custom)->value);
            self::assertSame(0, $application->run(['fix']));
            self::assertSame($fixed, file_get_contents($root . '/' . $file));
        } finally {
            unlink($root . '/' . $file);
            unlink($root . '/guard.yaml');
            rmdir($root);
        }
    }

    /**
     * @return iterable<string, array{string, string, string, string, bool|string}>
     */
    public static function providerFormats(): iterable
    {
        yield 'JSON' => ['json', '{"rules":{"custom":true}}', '/rules/feature/enabled', '/rules/custom', true];
        yield 'JSON5' => ['json5', '{/* keep data */ "rules":{"custom":true,},}', '/rules/feature/enabled', '/rules/custom', true];
        yield 'YAML' => ['yaml', "rules:\n  custom: true\n", '/rules/feature/enabled', '/rules/custom', true];
        yield 'YML' => ['yml', "rules:\n  custom: true\n", '/rules/feature/enabled', '/rules/custom', true];
        yield 'NEON' => ['neon', "rules:\n  custom: true\n", '/rules/feature/enabled', '/rules/custom', true];
        yield 'TOML' => ['toml', "[rules]\ncustom = true\n", '/rules/feature/enabled', '/rules/custom', true];
        yield 'XML' => ['xml', '<settings><rules custom="true"/></settings>', '/settings/rules/feature/@enabled', '/settings/rules/@custom', 'true'];
        yield 'PHP array' => ['php', '<?php return ["rules" => ["custom" => true]];', '/rules/feature/enabled', '/rules/custom', true];
        yield 'PHP' => ['php', '<?php return (new PhpCsFixer\Config())->setRules(["custom" => true]);', '/rules/feature/enabled', '/rules/custom', true];
    }

    /**
     * @dataProvider providerPhpUnitVersions
     */
    #[DataProvider('providerPhpUnitVersions')]
    public function testShippedPhpUnitAndDoctestPresetsEnableTheFeatures(int $major, string $file): void
    {
        $root = sys_get_temp_dir() . '/guard-phpunit-repair-' . uniqid('', true);
        mkdir($root);
        $package = dirname(__DIR__, 2);
        $source = '<phpunit><testsuites><testsuite name="unit"><directory>tests</directory></testsuite></testsuites></phpunit>';
        file_put_contents($root . '/' . $file, $source);
        file_put_contents($root . '/guard.yaml', Yaml::dump(['version' => 1, 'imports' => [
            $package . '/rules/phpunit' . $major . '.yaml',
            $package . '/rules/doctest' . $major . '.yaml',
        ]]));
        $application = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(0, $application->run(['fix']));
            self::assertSame(0, $application->run(['check']));
            $fixed = file_get_contents($root . '/' . $file);
            self::assertIsString($fixed);
            $document = new XmlDocument($fixed);
            self::assertSame('tests', $document->read('/phpunit/testsuites/testsuite[@name="unit"]/directory')->value);
            self::assertSame('vendor/k-kinzal/doctest-php/src/Doctest/' . ($major === 9 ? 'Legacy/LegacyDoctestSuite.php' : 'DoctestSuite.php'), $document->read('/phpunit/testsuites/testsuite[@name="doctest"]/file')->value);
            self::assertSame('src', $document->read($major === 9 ? '/phpunit/php/env[@name="DOCTEST_DIRECTORIES"]/@value' : '/phpunit/extensions/bootstrap[@class="Toolkit\Doctest\DoctestExtension"]/parameter[@name="directories"]/@value')->value);
            self::assertSame(0, $application->run(['fix']));
            self::assertSame($fixed, file_get_contents($root . '/' . $file));
        } finally {
            unlink($root . '/' . $file);
            unlink($root . '/guard.yaml');
            rmdir($root);
        }
    }

    /**
     * @dataProvider providerPhpUnitVersions
     */
    #[DataProvider('providerPhpUnitVersions')]
    public function testInitRequiresAndRepairsTheSuitesOfExistingTestDirectories(int $major, string $file): void
    {
        $root = sys_get_temp_dir() . '/guard-phpunit-suites-' . uniqid('', true);
        mkdir($root . '/tests/Unit', 0777, true);
        mkdir($root . '/tests/Integration', 0777, true);
        file_put_contents($root . '/' . $file, '<phpunit/>');
        $application = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(0, $application->run(['init', '--import=phpunit' . $major]));
            self::assertSame(1, $application->run(['check', '--query=testsuite']));
            self::assertSame(0, $application->run(['fix']));
            self::assertSame(0, $application->run(['check']));
            $fixed = file_get_contents($root . '/' . $file);
            self::assertIsString($fixed);
            $document = new XmlDocument($fixed);
            self::assertSame('tests/Unit', $document->read('/phpunit/testsuites/testsuite[@name="unit"]/directory')->value);
            self::assertSame('tests/Integration', $document->read('/phpunit/testsuites/testsuite[@name="integration"]/directory')->value);
        } finally {
            unlink($root . '/' . $file);
            unlink($root . '/guard.yaml');
            rmdir($root . '/tests/Unit');
            rmdir($root . '/tests/Integration');
            rmdir($root . '/tests');
            rmdir($root);
        }
    }

    /**
     * @return iterable<string, array{int, string}>
     */
    public static function providerPhpUnitVersions(): iterable
    {
        yield 'PHPUnit 9' => [9, 'phpunit9.xml.dist'];
        yield 'PHPUnit 10' => [10, 'phpunit10.xml.dist'];
        yield 'PHPUnit 11' => [11, 'phpunit11.xml.dist'];
        yield 'PHPUnit 12' => [12, 'phpunit12.xml.dist'];
        yield 'PHPUnit 13' => [13, 'phpunit.xml.dist'];
    }

    public function testPartialRulesPreserveCustomAttributesAndAnAlreadyAllowedValue(): void
    {
        $root = sys_get_temp_dir() . '/guard-partial-repair-' . uniqid('', true);
        mkdir($root);
        $source = '<settings><feature mode="strict" custom="keep"/><optional enabled="false"/></settings>';
        file_put_contents($root . '/settings.xml', $source);
        file_put_contents($root . '/guard.yaml', Yaml::dump(['version' => 1, 'configuration' => [
            ['id' => 'mode', 'file' => 'settings.xml', 'select' => '/settings/feature/@mode', 'assert' => ['one_of' => ['strict', 'normal']], 'repair' => 'normal'],
            ['id' => 'enabled', 'file' => 'settings.xml', 'select' => '/settings/feature/@enabled', 'assert' => ['equals' => 'true']],
            ['id' => 'optional', 'file' => 'settings.xml', 'select' => '/settings/optional/@enabled', 'level' => 'recommended', 'assert' => ['one_of' => ['true']]],
        ]], 8));
        $application = new Application($root, static function (string $text): void {
        });
        try {
            self::assertSame(0, $application->run(['fix']));
            self::assertSame(0, $application->run(['check']));
            $fixed = file_get_contents($root . '/settings.xml');
            self::assertIsString($fixed);
            $document = new XmlDocument($fixed);
            self::assertSame('strict', $document->read('/settings/feature/@mode')->value);
            self::assertSame('keep', $document->read('/settings/feature/@custom')->value);
            self::assertSame('true', $document->read('/settings/feature/@enabled')->value);
            self::assertSame('false', $document->read('/settings/optional/@enabled')->value);
        } finally {
            unlink($root . '/settings.xml');
            unlink($root . '/guard.yaml');
            rmdir($root);
        }
    }

}
