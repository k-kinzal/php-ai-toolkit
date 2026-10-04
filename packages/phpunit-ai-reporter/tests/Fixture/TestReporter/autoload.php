<?php

declare(strict_types=1);

$directory = __DIR__;
while (!is_file($directory . '/vendor/autoload.php')) {
    $parent = dirname($directory);
    if ($parent === $directory) {
        throw new RuntimeException('vendor/autoload.php was not found.');
    }
    $directory = $parent;
}

require $directory . '/vendor/autoload.php';
