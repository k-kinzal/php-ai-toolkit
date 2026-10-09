<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Discovery\Package;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Discovery\Package\RepositoryAddress;
use Toolkit\DocGen\DocGenException;

/**
 * @covers \Toolkit\DocGen\Discovery\Package\RepositoryAddress
 * @uses \Toolkit\DocGen\DocGenException
 */
#[CoversClass(RepositoryAddress::class)]
#[UsesClass(DocGenException::class)]
final class RepositoryAddressTest extends TestCase
{
    public function testReadKeepsAnAbsoluteAddressWithoutItsTrailingSlash(): void
    {
        self::assertSame('https://github.com/example/project', (new RepositoryAddress())->read('https://github.com/example/project'));
        self::assertSame('https://github.com/example/project', (new RepositoryAddress())->read('  https://github.com/example/project/  '));
        self::assertSame('http://git.example.com', (new RepositoryAddress())->read('http://git.example.com/'));
    }
    /**
     * @dataProvider providerUnusableValues
     */
    #[DataProvider('providerUnusableValues')]
    public function testReadIgnoresWhatAPageCannotLinkTo(mixed $value): void
    {
        self::assertNull((new RepositoryAddress())->read($value));
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function providerUnusableValues(): array
    {
        return [
            'nothing at all' => [null],
            'a value of another type' => [['https://github.com/example/project']],
            'an empty string' => [''],
            'a git transport' => ['git@github.com:example/project.git'],
            'a scheme nothing is served over' => ['git://github.com/example/project.git'],
            'without a host' => ['https:///example/project'],
        ];
    }
}
