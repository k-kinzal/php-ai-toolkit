<?php

declare(strict_types=1);

namespace Tests\Unit\PhpStan\Rule\Architecture;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermErrorBuilder;
use Toolkit\PhpStan\Rule\Shared\LineOrderedErrors;

/**
 * @covers \Toolkit\PhpStan\Rule\Architecture\ForbiddenFileTermErrorBuilder
 */
#[CoversClass(ForbiddenFileTermErrorBuilder::class)]
final class ForbiddenFileTermErrorBuilderTest extends TestCase
{
    public function testBuildIdentifiesTheTermPathAndDesignRemediation(): void
    {
        $error = (new ForbiddenFileTermErrorBuilder())->build('mysql', 'src/Query/Abstract/*', 42);

        self::assertSame('customRules.forbiddenFileTerm', $error->getIdentifier());
        self::assertSame(42, (new LineOrderedErrors())->lineOf($error));
        self::assertSame(
            'Forbidden term "mysql" appears in a file matched by path "src/Query/Abstract/*"; this is a design error because the concept does not belong in this layer. Redesign the responsibility boundary and move the concept and its behavior to the appropriate layer. Renaming, abbreviating, or deleting only the term is not a fix.',
            $error->getMessage()
        );
    }
}
