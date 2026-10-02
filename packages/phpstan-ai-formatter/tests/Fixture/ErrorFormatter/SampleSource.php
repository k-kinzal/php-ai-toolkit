<?php

declare(strict_types=1);

namespace Tests\Fixture\ErrorFormatter;

final class SampleSource
{
    private string $name;

    public function getName(): string
    {
        return $this->name;
    }
}
