<?php

namespace Tests\PhpStan\ForbidDependency\Project\tests;

use Tests\PhpStan\ForbidDependency\Project\examples\ExampleService;
use Tests\PhpStan\ForbidDependency\Project\examples\ExampleContract;
use Tests\PhpStan\ForbidDependency\Project\examples\ExampleTrait;
use Tests\PhpStan\ForbidDependency\Project\examples\ExampleAttribute;

#[ExampleAttribute]
class Derived extends ExampleService implements ExampleContract
{
    use ExampleTrait;

    public ?ExampleService $service;
}
