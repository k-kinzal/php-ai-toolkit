<?php

namespace Tests\PhpStan\ForbidDependency\Project\example;

require __DIR__ . '/../src/Reader.php';
echo file_get_contents(__FILE__);
new \Tests\PhpStan\ForbidDependency\Project\src\Reader();
