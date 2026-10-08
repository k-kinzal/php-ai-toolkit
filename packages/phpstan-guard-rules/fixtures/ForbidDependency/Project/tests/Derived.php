<?php

namespace Tests\Fixture\ForbidDependency\Project\tests;

use Tests\Fixture\ForbidDependency\Project\examples\ExampleService;
use Tests\Fixture\ForbidDependency\Project\examples\ExampleContract;
use Tests\Fixture\ForbidDependency\Project\examples\ExampleTrait;
use Tests\Fixture\ForbidDependency\Project\examples\ExampleAttribute;

#[ExampleAttribute]
class Derived extends ExampleService implements ExampleContract
{
    use ExampleTrait;

    public ?ExampleService $service;
}
