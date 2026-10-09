<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Action\Config;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Action\Config\RepositoryUrl;
use Toolkit\DocGen\Discovery\Package\RepositoryAddress;
use Toolkit\DocGen\DocGenException;

/**
 * @covers \Toolkit\DocGen\Action\Config\RepositoryUrl
 * @uses \Toolkit\DocGen\DocGenException
 * @uses \Toolkit\DocGen\Discovery\Package\RepositoryAddress
 */
#[CoversClass(RepositoryUrl::class)]
#[UsesClass(DocGenException::class)]
#[UsesClass(RepositoryAddress::class)]
final class RepositoryUrlTest extends TestCase
{
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
        $this->expectExceptionMessage('Invalid --repository value: git@github.com:example/project.git. Use the absolute address of the repository the project lives in, such as https://github.com/example/project.');

        (new RepositoryUrl())->normalize('git@github.com:example/project.git');
    }
}
