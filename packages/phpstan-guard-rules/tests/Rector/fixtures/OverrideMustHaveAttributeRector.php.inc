<?php

namespace RectorFixtures\Overrides;

abstract class ParentClass
{
    public function run(): void {}
    private function hidden(): void {}
    public function __construct() {}
    abstract public function implement(): void;
}

class ChildClass extends ParentClass
{
    public function run(): void {}
    public function hidden(): void {}
    public function __construct() {}
    public function implement(): void {}
}

class Grandchild extends ChildClass
{
    #[\Override]
    public function run(): void {}
}

-----
<?php

namespace RectorFixtures\Overrides;

abstract class ParentClass
{
    public function run(): void {}
    private function hidden(): void {}
    public function __construct() {}
    abstract public function implement(): void;
}

class ChildClass extends ParentClass
{
    #[\Override]
    public function run(): void {}
    public function hidden(): void {}
    public function __construct() {}
    public function implement(): void {}
}

class Grandchild extends ChildClass
{
    #[\Override]
    public function run(): void {}
}
