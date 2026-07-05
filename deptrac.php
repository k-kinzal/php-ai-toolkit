<?php

declare(strict_types=1);

$deptracEntrypoints = [
    __DIR__ . '/vendor/bin/deptrac',
    __DIR__ . '/vendor/deptrac/deptrac/deptrac.php',
];

foreach ($deptracEntrypoints as $deptracEntrypoint) {
    if (!is_file($deptracEntrypoint)) {
        continue;
    }

    $command = array_map(
        static fn (string $argument): string => escapeshellarg($argument),
        [
            PHP_BINARY,
            $deptracEntrypoint,
            ...array_slice($argv, 1),
        ],
    );

    passthru(implode(' ', $command), $exitCode);
    exit($exitCode);
}

fwrite(
    STDERR,
    'Could not find Deptrac. Run "composer install" first.' . PHP_EOL,
);

exit(1);
