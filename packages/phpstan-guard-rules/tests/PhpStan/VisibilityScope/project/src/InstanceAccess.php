<?php
namespace Tests\PhpStan\VisibilityScope\Instances\Package;
class Service
{
    /** @visibility namespace */
    public function internal(): void {}
    /** @visibility namespace */
    public string $state = '';
    public function allowed(): void {}
    public function sameNamespace(): void { $this->internal(); }
    public string $internal = '';
    /** @visibility namespace */
    public string $allowed = '';
}
class Child extends Service {}
namespace Tests\PhpStan\VisibilityScope\Instances\Outside;
function access(\Tests\PhpStan\VisibilityScope\Instances\Package\Child $service, ?\Tests\PhpStan\VisibilityScope\Instances\Package\Service $nullable): void
{
    $service->INTERNAL();
    $service->state = 'changed';
    $nullable?->internal();
    $state = $nullable?->state;
    $service->allowed();
}
