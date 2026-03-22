<?php

declare(strict_types=1);

namespace Tests\Fixture\TestReporter;

class SampleTest
{
    public function testGetName(): void
    {
        self::assertSame('John', $this->service->getName());
    }

    public function testDelete(): void
    {
        $this->repo->delete($id);
    }
}
