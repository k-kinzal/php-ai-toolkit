<?php

declare(strict_types=1);

namespace Tests\Unit\DocGen\Cache;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\DocGen\Cache\SourceFileKey;

/**
 * @covers \Toolkit\DocGen\Cache\SourceFileKey
 */
#[CoversClass(SourceFileKey::class)]
final class SourceFileKeyTest extends TestCase
{
    public function testOfNamesTheSameFileTheSameWay(): void
    {
        $key = new SourceFileKey();

        self::assertSame(
            $key->of('fingerprint', '<?php class A {}', 'src/A.php', 'demo/app', false),
            $key->of('fingerprint', '<?php class A {}', 'src/A.php', 'demo/app', false),
        );
    }

    public function testOfNamesEveryDifferenceDifferently(): void
    {
        $key = new SourceFileKey();
        $base = $key->of('fingerprint', '<?php class A {}', 'src/A.php', 'demo/app', false);

        self::assertNotSame($base, $key->of('other', '<?php class A {}', 'src/A.php', 'demo/app', false));
        self::assertNotSame($base, $key->of('fingerprint', '<?php class B {}', 'src/A.php', 'demo/app', false));
        self::assertNotSame($base, $key->of('fingerprint', '<?php class A {}', 'src/B.php', 'demo/app', false));
        self::assertNotSame($base, $key->of('fingerprint', '<?php class A {}', 'src/A.php', 'demo/other', false));
        self::assertNotSame($base, $key->of('fingerprint', '<?php class A {}', 'src/A.php', 'demo/app', true));
    }
}
