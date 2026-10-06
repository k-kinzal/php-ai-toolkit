<?php

declare(strict_types=1);

namespace Tests\Unit\Structure\Php;

use JsonException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \Guard\Structure\Php\TokenParser
 * @uses \Guard\Collect\DirectoryListing
 * @uses \Guard\Collect\FileRecord
 * @uses \Guard\Collect\FileSet
 * @uses \Guard\Collect\Filesystem\Entry
 * @uses \Guard\Collect\Input
 * @uses \Guard\Collect\InputSet
 * @uses \Guard\Collect\Selection
 * @uses \Guard\Collect\StructuredFile
 * @uses \Guard\Config\Configuration
 * @uses \Guard\Execution\Context
 * @uses \Guard\Execution\FileChange
 * @uses \Guard\Execution\Plan
 * @uses \Guard\Extension\PolicyBinding
 * @uses \Guard\Policy\PolicyException
 * @uses \Guard\Reporting\Finding
 * @uses \Guard\Structure\Php\Tokens
 * @uses \Guard\Structure\Source
 */
#[CoversClass(\Guard\Structure\Php\TokenParser::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\DirectoryListing::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileRecord::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\FileSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Filesystem\Entry::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Input::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\InputSet::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\Selection::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Collect\StructuredFile::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Config\Configuration::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Context::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\FileChange::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Execution\Plan::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Extension\PolicyBinding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Policy\PolicyException::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Reporting\Finding::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Php\Tokens::class)]
#[\PHPUnit\Framework\Attributes\UsesClass(\Guard\Structure\Source::class)]
final class TokenParserTest extends TestCase
{
    /**
     * @throws JsonException
     * @throws \Nette\Neon\Exception
     */
    public function testStructureTokenizesWithoutExecutingPhp(): void
    {
        $tokens = (new \Guard\Structure\Php\TokenParser())->structure(new \Guard\Structure\Source('<?php throw new Exception("must not execute");', []));
        self::assertSame(T_OPEN_TAG, $tokens->all()[0]->id);
        self::assertSame(T_THROW, $tokens->all()[1]->id);
    }
}
