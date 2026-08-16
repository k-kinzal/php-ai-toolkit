<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Config;

use PhpAiToolkit\DocGen\Config\RepositoryUrl;
use PhpAiToolkit\DocGen\DocGenException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RepositoryUrl::class)]
#[UsesClass(DocGenException::class)]
final class RepositoryUrlTest extends TestCase
{
    public function testReadKeepsAnAbsoluteAddressWithoutItsTrailingSlash(): void
    {
        self::assertSame('https://github.com/example/project', (new RepositoryUrl())->read('https://github.com/example/project'));
        self::assertSame('https://github.com/example/project', (new RepositoryUrl())->read('  https://github.com/example/project/  '));
        self::assertSame('http://git.example.com', (new RepositoryUrl())->read('http://git.example.com/'));
    }

    /**
     * @dataProvider providerUnusableValues
     */
    #[DataProvider('providerUnusableValues')]
    public function testReadIgnoresWhatAPageCannotLinkTo(mixed $value): void
    {
        self::assertNull((new RepositoryUrl())->read($value));
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

    public function testNormalizeReadsAMissingOrEmptyValueAsNoRepository(): void
    {
        self::assertNull((new RepositoryUrl())->normalize(null));
        self::assertNull((new RepositoryUrl())->normalize(''));
        self::assertNull((new RepositoryUrl())->normalize('   '));
    }

    public function testNormalizeKeepsAConfiguredAddress(): void
    {
        self::assertSame('https://github.com/example/project', (new RepositoryUrl())->normalize('https://github.com/example/project/'));
    }

    public function testNormalizeRejectsAnAddressNoPageCanLinkTo(): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid repository: git@github.com:example/project.git. Use the absolute address of the repository the project lives in, such as https://github.com/example/project.');

        (new RepositoryUrl())->normalize('git@github.com:example/project.git');
    }
}
