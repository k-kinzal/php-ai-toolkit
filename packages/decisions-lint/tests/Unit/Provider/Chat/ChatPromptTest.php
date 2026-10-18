<?php

declare(strict_types=1);

namespace Tests\Unit\Provider\Chat;

use DecisionsLint\Decision\Inquiry;
use DecisionsLint\Decision\Question;
use DecisionsLint\Provider\Chat\ChatPrompt;
use DecisionsLint\Provider\OpenAi\InputRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Provider\Chat\ChatPrompt
 * @uses \DecisionsLint\Decision\Inquiry
 * @uses \DecisionsLint\Decision\Question
 * @uses \DecisionsLint\Provider\OpenAi\InputRenderer
 */
#[CoversClass(ChatPrompt::class)]
#[UsesClass(Inquiry::class)]
#[UsesClass(Question::class)]
#[UsesClass(InputRenderer::class)]
final class ChatPromptTest extends TestCase
{
    public function testMessagesOpenWithTheSystemPromptAndListTheQuestionsAfterTheSubject(): void
    {
        $inquiry = new Inquiry(['file' => 'src/A.php', 'declaration' => 'class A {}'], [new Question('p', Question::PREDICATE, 'Does it fit?')]);
        $messages = (new ChatPrompt(new InputRenderer()))->messages($inquiry);
        self::assertSame('system', $messages[0]['role']);
        self::assertSame(ChatPrompt::SYSTEM, $messages[0]['content']);
        self::assertSame('user', $messages[1]['role']);
        self::assertStringStartsWith("## file\n\nsrc/A.php\n\n## declaration\n\n```php\nclass A {}\n```\n\n## Questions\n\n### p\nDoes it fit?\nAnswer with the probability", $messages[1]['content']);
    }

    public function testDescribeListsOptionsAndLevels(): void
    {
        $prompt = new ChatPrompt();
        self::assertSame("### c\nPick.\nAnswer with exactly one of these options in \"choice\":\n- a: A\n- b: B", $prompt->describe(new Question('c', Question::CHOICE, 'Pick.', ['a' => 'A', 'b' => 'B'])));
        self::assertSame("### s\nRate.\nAnswer with the number of the level that fits best in \"score\":\n- 0: low\n- 1: high", $prompt->describe(new Question('s', Question::SCORE, 'Rate.', [], ['low', 'high'])));
    }

    public function testSchemaRequiresOneObjectPerQuestion(): void
    {
        $schema = (new ChatPrompt())->schema(new Inquiry([], [new Question('p', Question::PREDICATE, 'P?'), new Question('c', Question::CHOICE, 'C?', ['a' => 'A'])]));
        self::assertSame('object', $schema['type']);
        self::assertSame(['p', 'c'], $schema['required']);
        self::assertFalse($schema['additionalProperties']);
        $properties = $schema['properties'];
        self::assertIsArray($properties);
        self::assertSame(['p', 'c'], array_keys($properties));
    }

    public function testAnswerSchemaMatchesTheQuestionType(): void
    {
        $prompt = new ChatPrompt();
        $unit = ['type' => 'number', 'minimum' => 0, 'maximum' => 1];
        self::assertSame(['type' => 'object', 'properties' => ['probability' => $unit], 'required' => ['probability'], 'additionalProperties' => false], $prompt->answerSchema(new Question('p', Question::PREDICATE, 'P?')));
        self::assertSame(['type' => 'object', 'properties' => ['choice' => ['type' => 'string', 'enum' => ['a', 'b']], 'confidence' => $unit], 'required' => ['choice', 'confidence'], 'additionalProperties' => false], $prompt->answerSchema(new Question('c', Question::CHOICE, 'C?', ['a' => 'A', 'b' => 'B'])));
        self::assertSame(['type' => 'object', 'properties' => ['score' => ['type' => 'integer', 'minimum' => 0, 'maximum' => 2], 'confidence' => $unit], 'required' => ['score', 'confidence'], 'additionalProperties' => false], $prompt->answerSchema(new Question('s', Question::SCORE, 'S?', [], ['x', 'y', 'z'])));
    }
}
