<?php

namespace RectorFixtures\SingleLine;

/** A public class. */
class Example
{
    /** @var string */
    public $name = 'ok';
    /** A constant. */
    public const NAME = 'ok';
    /** Executes work. */
    public function run(): void {}
    /** Keep private formatting. */
    private function hidden(): void {}
    /** Keep protected formatting. */
    protected $internal;
}

$anonymous = new class {
    /** Anonymous method. */
    public function run(): void {}
};

-----
<?php

namespace RectorFixtures\SingleLine;

/**
 * A public class.
 */
class Example
{
    /**
     * @var string
     */
    public $name = 'ok';
    /**
     * A constant.
     */
    public const NAME = 'ok';
    /**
     * Executes work.
     */
    public function run(): void {}
    /** Keep private formatting. */
    private function hidden(): void {}
    /** Keep protected formatting. */
    protected $internal;
}

$anonymous = new class {
    /** Anonymous method. */
    public function run(): void {}
};
