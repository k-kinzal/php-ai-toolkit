<?php

declare(strict_types=1);

namespace App\Fixture\ForbidDescriptivePhpDocInTestClass;

class NonTestClass
{
    /**
     * This method has descriptive PHPDoc but is not in a test class.
     */
    public function testLikeName(): void
    {
    }
}
