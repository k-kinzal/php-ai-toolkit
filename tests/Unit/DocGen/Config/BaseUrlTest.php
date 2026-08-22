<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Config;

use PhpAiToolkit\DocGen\Config\BaseUrl;
use PhpAiToolkit\DocGen\DocGenException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BaseUrl::class)]
#[UsesClass(DocGenException::class)]
final class BaseUrlTest extends TestCase
{
    public function testNormalizeKeepsAnAbsoluteAddress(): void
    {
        self::assertSame('https://example.github.io/project', (new BaseUrl())->normalize('https://example.github.io/project'));
    }

    public function testNormalizeDropsTheTrailingSlash(): void
    {
        self::assertSame('https://example.github.io/project', (new BaseUrl())->normalize('https://example.github.io/project/'));
        self::assertSame('https://example.com', (new BaseUrl())->normalize('https://example.com/'));
    }

    public function testNormalizeReadsAMissingOrEmptyValueAsNoAddress(): void
    {
        self::assertNull((new BaseUrl())->normalize(null));
        self::assertNull((new BaseUrl())->normalize(''));
        self::assertNull((new BaseUrl())->normalize('   '));
    }

    /**
     * @dataProvider providerRejectedAddresses
     */
    #[DataProvider('providerRejectedAddresses')]
    public function testNormalizeRejectsAnAddressNothingCanBeFetchedFrom(string $value): void
    {
        $this->expectException(DocGenException::class);
        $this->expectExceptionMessage('Invalid --base-url value: ' . $value);

        (new BaseUrl())->normalize($value);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function providerRejectedAddresses(): array
    {
        return [
            'without a scheme' => ['example.github.io/project'],
            'with a scheme nothing is served over' => ['ftp://example.github.io/project'],
            'with a query' => ['https://example.github.io/project?page=1'],
            'with a fragment' => ['https://example.github.io/project#top'],
            'without a host' => ['https:///project'],
        ];
    }
}
