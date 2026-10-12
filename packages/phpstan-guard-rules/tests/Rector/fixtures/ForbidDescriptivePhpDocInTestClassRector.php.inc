<?php

namespace Tests\Unit\RectorFixtures;

/** Test class prose. */
class ExampleTest
{
    /**
     * Test method prose.
     *
     * @dataProvider provideCases
     * @return array{
     *     name: string,
     *     age: int
     * }
     */
    public function testRun(): array { return []; }

    /** @dataProvider provideCases */
    public function testAlreadyTagged(): void {}
}

namespace Tests\Other;

/** Preserve unrestricted prose. */
class OtherTest {}

-----
<?php

namespace Tests\Unit\RectorFixtures;

class ExampleTest
{
    /**
     * @dataProvider provideCases
     * @return array{
     *     name: string,
     *     age: int
     * }
     */
    public function testRun(): array { return []; }

    /** @dataProvider provideCases */
    public function testAlreadyTagged(): void {}
}

namespace Tests\Other;

/** Preserve unrestricted prose. */
class OtherTest {}
