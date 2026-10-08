<?php

namespace Tests\Fixture\ForbidDependency\Project\tests;

use function file_get_contents as read_text;
use Tests\Fixture\ForbidDependency\Project\src\Reader;
use function Tests\Fixture\ForbidDependency\Project\src\read_config;

require __DIR__ . '/../examples/ExampleService.php';
include_once dirname(__DIR__) . '/examples/ExampleContract.php';
read_text(__DIR__ . '/../examples/input.json');
file_get_contents(offset: 0, filename: __DIR__ . '/../examples/input.json');
fopen(mode: 'rb', filename: __DIR__ . '/../examples/input.json');
file(__DIR__ . '/../examples/input.json');
readfile(__DIR__ . '/../examples/input.json');
hash_file('sha256', __DIR__ . '/../examples/input.json');
new \SplFileObject(__DIR__ . '/../examples/input.json');
Reader::load(path: __DIR__ . '/../examples/input.json');
(new Reader())->read(__DIR__ . '/../examples/input.json');
read_config(__DIR__ . '/../examples/input.json');
file_get_contents('file://' . __DIR__ . '/../examples/input.json');
require __DIR__ . '/../example/local.php';
