<?php

namespace Tests\PhpStan\ForbidDependency\Project\tests;

file_get_contents(__DIR__ . '/../fixtures/input.json');
file_get_contents(__DIR__ . '/../examples-backup/input.json');
file_get_contents(__DIR__ . '/../test-support/input.json');
require __DIR__ . '/../test-support/bootstrap.php';
