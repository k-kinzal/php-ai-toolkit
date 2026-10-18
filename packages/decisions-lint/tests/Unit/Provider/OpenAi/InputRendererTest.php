<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\OpenAi;

use DecisionsLint\Provider\OpenAi\InputRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\OpenAi\InputRenderer
 */
#[CoversClass(InputRenderer::class)]
final class InputRendererTest extends TestCase
{
    public function testRenderWritesHeadingsAndFencesSourceSections(): void
    {
        $text = (new InputRenderer())->render(['file' => 'src/A.php', 'declaration' => "<?php\nclass A {}\n", 'class_source' => 'class A {}']);
        self::assertSame("## file\n\nsrc/A.php\n\n## declaration\n\n```php\n<?php\nclass A {}\n```\n\n## class source\n\n```php\nclass A {}\n```\n", $text);
    }
}
