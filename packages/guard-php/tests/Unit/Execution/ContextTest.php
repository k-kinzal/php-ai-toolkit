<?php

declare(strict_types=1);

namespace Tests\Unit\Execution;

use Guard\Config\Configuration;
use Guard\Execution\Context;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Execution\Context
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Policy\Rule
 * @uses \Guard\Reporting\Finding
 */
#[CoversClass(Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\Rule::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
final class ContextTest extends TestCase
{
    public function testKeepsProjectAndCommandInputsSeparate(): void
    {
        $configuration = new Configuration('/project', null, null, null, []);
        $context = new Context($configuration, '/project/custom.yaml', true);
        self::assertSame($configuration, $context->configuration);
        self::assertSame('/project/custom.yaml', $context->configPath);
        self::assertTrue($context->repair);
    }

}
