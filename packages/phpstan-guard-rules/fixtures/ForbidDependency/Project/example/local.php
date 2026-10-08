<?php

namespace Tests\Fixture\ForbidDependency\Project\example;

require __DIR__ . '/../src/Reader.php';
echo file_get_contents(__FILE__);
new \Tests\Fixture\ForbidDependency\Project\src\Reader();
