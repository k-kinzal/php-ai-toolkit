<?php

declare(strict_types=1);

namespace Tests\Unit\Config;

use DecisionsLint\Config\SchemaCatalog;
use DecisionsLint\Config\SchemaValidator;
use DecisionsLint\Diagnostic\LintException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\UsesClass;
use PHPUnit\Framework\TestCase;

/**
 * @covers \DecisionsLint\Config\SchemaValidator
 * @uses \DecisionsLint\Config\SchemaCatalog
 * @uses \DecisionsLint\Diagnostic\LintException
 */
#[CoversClass(SchemaValidator::class)]
#[UsesClass(SchemaCatalog::class)]
#[UsesClass(LintException::class)]
final class SchemaValidatorTest extends TestCase
{
    public function testValidateAcceptsACompleteConfiguration(): void
    {
        $this->expectNotToPerformAssertions();
        (new SchemaValidator(new SchemaCatalog()))->validate([
            'version' => 1,
            'provider' => ['name' => 'jev', 'model' => 'jev-latest', 'api_key_env' => 'TYPESAFE_API_KEY', 'base_url' => 'https://api.typesafe.ai', 'timeout' => 30, 'concurrency' => 4],
            'paths' => ['src'],
            'exclude' => [],
            'fail_on' => 'warning',
            'imports' => ['rules/a.yaml'],
            'rules' => ['a.b' => ['severity' => 'info', 'decision' => ['threshold' => 0.9]]],
        ], SchemaCatalog::DECISIONS, 'decisions.yaml');
    }

    /**
     * @dataProvider providerConfigurationProblems
     * @param array<string, mixed> $document
     */
    #[DataProvider('providerConfigurationProblems')]
    public function testValidateNamesThePathTheProblemAndTheFix(array $document, string $expected): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage($expected);
        (new SchemaValidator())->validate($document, SchemaCatalog::DECISIONS, 'decisions.yaml');
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function providerConfigurationProblems(): iterable
    {
        yield 'missing version' => [[], 'decisions.yaml: the document is missing the key "version". Add it: the configuration format version; always 1.'];
        yield 'wrong version' => [['version' => 2], 'decisions.yaml: version must be 1. Set it to 1.'];
        yield 'unknown key' => [['version' => 1, 'pahts' => ['src']], 'decisions.yaml: the document has the unknown key "pahts". Remove it or rename it to one of: version, provider, paths, exclude, fail_on, imports, rules.'];
        yield 'enum' => [['version' => 1, 'provider' => ['name' => 'anthropic']], 'decisions.yaml: provider.name must be one of "jev", "openai", "ollama", "localai", "openai-compatible", not "anthropic". Replace it with one of those values.'];
        yield 'type' => [['version' => 1, 'provider' => ['timeout' => 'fast']], 'decisions.yaml: provider.timeout must be an integer, not "fast". Change the value to an integer.'];
        yield 'minimum' => [['version' => 1, 'provider' => ['timeout' => 0]], 'decisions.yaml: provider.timeout must be at least 1, not 0. Raise the value.'];
        yield 'maximum' => [['version' => 1, 'provider' => ['concurrency' => 65]], 'decisions.yaml: provider.concurrency must be at most 64, not 65. Lower the value.'];
        yield 'pattern' => [['version' => 1, 'provider' => ['api_key_env' => '1BAD']], 'decisions.yaml: provider.api_key_env must be the environment variable that holds the API key, never the key itself; local servers run without one, not "1BAD". Change the value.'];
        yield 'min length' => [['version' => 1, 'paths' => ['']], 'decisions.yaml: paths[0] must not be empty. Write a value.'];
        yield 'min items' => [['version' => 1, 'paths' => []], 'decisions.yaml: paths must have at least 1 entry, not 0. Add entries.'];
        yield 'list expected' => [['version' => 1, 'paths' => 'src'], 'decisions.yaml: paths must be a list, not "src". Change the value to a list.'];
        yield 'rule id' => [['version' => 1, 'rules' => ['Bad Id' => []]], 'decisions.yaml: rules has the key "Bad Id", which is not a rule id made of lower-case letters, digits, dots and hyphens, such as cohesion.method-belongs-to-class. Rename the key.'];
        yield 'nested rule key' => [['version' => 1, 'rules' => ['a' => ['severty' => 'error']]], 'decisions.yaml: rules.a has the unknown key "severty". Remove it or rename it to one of: description, severity, enabled, paths, exclude, target, context, decision, violations.'];
        yield 'nested enum' => [['version' => 1, 'rules' => ['a' => ['decision' => ['type' => 'boolean']]]], 'decisions.yaml: rules.a.decision.type must be one of "predicate", "choice", "score", not "boolean".'];
        yield 'answer type' => [['version' => 1, 'rules' => ['a' => ['violations' => [['answer' => 1, 'message' => 'm', 'fix' => 'f']]]]], 'decisions.yaml: rules.a.violations[0].answer must be true or false or a string, not 1.'];
        yield 'levels count' => [['version' => 1, 'rules' => ['a' => ['decision' => ['levels' => ['only']]]]], 'decisions.yaml: rules.a.decision.levels must have at least 2 entries, not 1.'];
        yield 'mapping expected' => [['version' => 1, 'provider' => 'jev'], 'decisions.yaml: provider must be a mapping, not "jev". Change the value to a mapping.'];
    }

    public function testValidateRequiresTheRulePartsInARuleFile(): void
    {
        $validator = new SchemaValidator();
        $validator->validate(['version' => 1, 'rules' => ['a' => ['target' => ['kind' => 'method'], 'decision' => ['type' => 'predicate', 'instructions' => 'Q?'], 'violations' => [['answer' => false, 'message' => 'm', 'fix' => 'f']]]]], SchemaCatalog::RULES, 'rules/a.yaml');
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('rules/a.yaml: rules.a is missing the key "decision". Add it.');
        $validator->validate(['version' => 1, 'rules' => ['a' => ['target' => ['kind' => 'method']]]], SchemaCatalog::RULES, 'rules/a.yaml');
    }

    public function testCheckFollowsReferencesIntoAnotherSchemaFile(): void
    {
        $catalog = new SchemaCatalog();
        $validator = new SchemaValidator($catalog);
        $validator->check('a.b', ['$ref' => 'rules.schema.json#/$defs/ruleId'], $catalog->schema(SchemaCatalog::DECISIONS), $catalog, 'f', 'id');
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('f: id must be a rule id made of lower-case letters');
        $validator->check('A', ['$ref' => 'rules.schema.json#/$defs/ruleId'], $catalog->schema(SchemaCatalog::DECISIONS), $catalog, 'f', 'id');
    }

    public function testCheckScalarAcceptsAnyOfSeveralTypes(): void
    {
        $validator = new SchemaValidator();
        $validator->checkScalar(true, ['type' => ['boolean', 'string']], 'f', 'p');
        $validator->checkScalar('x', ['type' => ['boolean', 'string']], 'f', 'p');
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('f: p must be true or false or a string, not 1.5.');
        $validator->checkScalar(1.5, ['type' => ['boolean', 'string']], 'f', 'p');
    }

    public function testCheckBoundsAcceptsTheEdges(): void
    {
        $validator = new SchemaValidator();
        $validator->checkBounds(0, ['minimum' => 0, 'maximum' => 1], 'f', 'p');
        $validator->checkBounds(1.0, ['minimum' => 0, 'maximum' => 1], 'f', 'p');
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('f: p must be at least 0, not -0.5. Raise the value.');
        $validator->checkBounds(-0.5, ['minimum' => 0], 'f', 'p');
    }

    public function testCheckStringReportsALongMinimumLength(): void
    {
        $validator = new SchemaValidator();
        $validator->checkString('abc', ['minLength' => 3, 'pattern' => '^a'], 'f', 'p');
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('f: p must be at least 3 characters long. Write a value.');
        $validator->checkString('ab', ['minLength' => 3], 'f', 'p');
    }

    public function testCheckStringDescribesAPatternWithoutADescription(): void
    {
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('f: p must be a value matching ^a, not "b". Change the value.');
        (new SchemaValidator())->checkString('b', ['pattern' => '^a'], 'f', 'p');
    }

    public function testCheckObjectLeavesFreeMappingsAlone(): void
    {
        $catalog = new SchemaCatalog();
        $validator = new SchemaValidator($catalog);
        $validator->checkObject(['anything' => 1], ['type' => 'object'], [], $catalog, 'f', 'p');
        $validator->checkObject(['known' => 1, 'extra' => 2], ['properties' => ['known' => ['type' => 'integer']]], [], $catalog, 'f', 'p');
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('f: p.extra must be a string, not 2.');
        $validator->checkObject(['extra' => 2], ['additionalProperties' => ['type' => 'string']], [], $catalog, 'f', 'p');
    }

    public function testCheckPropertyNameRephrasesTheFailureAroundTheKey(): void
    {
        $catalog = new SchemaCatalog();
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('f: rules has the key "x y", which is not a valid key. Rename the key.');
        (new SchemaValidator($catalog))->checkPropertyName('x y', ['pattern' => '^[a-z]+$'], [], $catalog, 'f', 'rules');
    }

    public function testCheckListReportsTooManyEntriesAndValidatesEach(): void
    {
        $catalog = new SchemaCatalog();
        $validator = new SchemaValidator($catalog);
        $validator->checkList(['a', 'b'], ['maxItems' => 2, 'items' => ['type' => 'string']], [], $catalog, 'f', 'p');
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('f: p must have at most 1 entries, not 2. Remove entries.');
        $validator->checkList(['a', 'b'], ['maxItems' => 1], [], $catalog, 'f', 'p');
    }

    public function testResolveRejectsADanglingReference(): void
    {
        $catalog = new SchemaCatalog();
        $this->expectException(LintException::class);
        $this->expectExceptionMessage('Schema reference "#/$defs/missing" points to nothing.');
        (new SchemaValidator($catalog))->resolve('#/$defs/missing', $catalog->schema(SchemaCatalog::RULES), $catalog);
    }

    /**
     * @dataProvider providerHasType
     */
    #[DataProvider('providerHasType')]
    public function testHasTypeMatchesJsonTypes(mixed $value, string $type, bool $expected): void
    {
        self::assertSame($expected, (new SchemaValidator())->hasType($value, [$type]));
    }

    /**
     * @return iterable<string, array{mixed, string, bool}>
     */
    public static function providerHasType(): iterable
    {
        yield 'empty array is an object' => [[], 'object', true];
        yield 'empty array is a list' => [[], 'array', true];
        yield 'list is not an object' => [[1], 'object', false];
        yield 'mapping is not a list' => [['a' => 1], 'array', false];
        yield 'integer is a number' => [1, 'number', true];
        yield 'float is not an integer' => [1.5, 'integer', false];
        yield 'null' => [null, 'null', true];
        yield 'unknown type' => ['x', 'date', false];
    }

    public function testExpectationPrefersTheDescriptionInLowerCaseWithoutAPeriod(): void
    {
        $validator = new SchemaValidator();
        self::assertSame('the environment variable that holds the key', $validator->expectation(['description' => 'The environment variable that holds the key.'], 'fallback'));
        self::assertSame('fallback', $validator->expectation([], 'fallback'));
    }

    public function testProblemFormatsTheMessage(): void
    {
        self::assertSame('f: the document is broken. Fix it.', (new SchemaValidator())->problem('f', '', 'is broken', 'Fix it.')->getMessage());
    }

    public function testDescribeQuotesStringsAndNamesShapes(): void
    {
        $validator = new SchemaValidator();
        self::assertSame('"x"', $validator->describe('x'));
        self::assertSame('an empty list', $validator->describe([]));
        self::assertSame('a list', $validator->describe([1]));
        self::assertSame('a mapping', $validator->describe(['a' => 1]));
        self::assertSame('true', $validator->describe(true));
    }

    public function testDescribeTypesJoinsReadableNames(): void
    {
        self::assertSame('a mapping or a list or a number or null', (new SchemaValidator())->describeTypes(['object', 'array', 'number', 'null']));
    }

    public function testTypesReadsOneOrSeveralTypes(): void
    {
        self::assertSame(['string'], (new SchemaValidator())->types('string'));
        self::assertSame(['string', 'null'], (new SchemaValidator())->types(['string', 1, 'null']));
    }

    public function testObjectReturnsAMappingOrNothing(): void
    {
        self::assertSame(['a' => 1], (new SchemaValidator())->object(['a' => 1]));
        self::assertSame([], (new SchemaValidator())->object('x'));
    }

    public function testListReturnsAListOrNothing(): void
    {
        self::assertSame([1, 2], (new SchemaValidator())->list(['a' => 1, 'b' => 2]));
        self::assertSame([], (new SchemaValidator())->list(null));
    }
}
