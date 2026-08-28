<?php

declare(strict_types=1);

use Composer\InstalledVersions;

$autoloadPath = __DIR__ . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    fwrite(STDERR, 'Composer dependencies are missing. Run "composer install" first.' . PHP_EOL);
    exit(1);
}

require $autoloadPath;

if (!InstalledVersions::isInstalled('phpunit/phpunit')) {
    fwrite(STDERR, 'PHPUnit is not installed. Require phpunit/phpunit before running tests.' . PHP_EOL);
    exit(1);
}

$phpUnitVersion = InstalledVersions::getVersion('phpunit/phpunit');

if ($phpUnitVersion === null) {
    fwrite(STDERR, 'The installed phpunit/phpunit package does not expose a version. Install a tagged release.' . PHP_EOL);
    exit(1);
}

$phpUnitMajor = explode('.', $phpUnitVersion)[0];
$configurations = [
    '9' => 'phpunit9.xml.dist',
    '10' => 'phpunit10.xml.dist',
    '11' => 'phpunit11.xml.dist',
    '12' => 'phpunit12.xml.dist',
    '13' => 'phpunit.xml.dist',
];
$configuration = $configurations[$phpUnitMajor] ?? null;

if ($configuration === null) {
    fwrite(STDERR, sprintf(
        'Installed PHPUnit major %s is unsupported. Add its configuration to phpunit.php.',
        $phpUnitMajor,
    ) . PHP_EOL);
    exit(1);
}

$configurationPath = __DIR__ . '/' . $configuration;

if (!is_file($configurationPath)) {
    fwrite(STDERR, sprintf(
        'PHPUnit %s requires %s. Copy that major\'s toolkit configuration to the project root.',
        $phpUnitMajor,
        $configuration,
    ) . PHP_EOL);
    exit(1);
}

$arguments = array_slice($argv, 1);
$parallel = ($arguments[0] ?? null) === '--parallel';

if ($parallel) {
    array_shift($arguments);
}

$runner = __DIR__ . '/vendor/bin/' . ($parallel ? 'paratest' : 'phpunit');

if (!is_file($runner)) {
    fwrite(STDERR, sprintf(
        '%s is not installed. Require the matching development dependency first.',
        $parallel ? 'ParaTest' : 'PHPUnit',
    ) . PHP_EOL);
    exit(1);
}

$memoryLimit = (string) ini_get('memory_limit');

if ($parallel) {
    array_unshift($arguments, '--passthru-php=-d memory_limit=' . $memoryLimit);
}

$command = array_map(
    static fn (string $argument): string => escapeshellarg($argument),
    [
        PHP_BINARY,
        '-d',
        'memory_limit=' . $memoryLimit,
        $runner,
        '--configuration',
        $configurationPath,
        ...$arguments,
    ],
);

passthru(implode(' ', $command), $exitCode);
exit($exitCode);
