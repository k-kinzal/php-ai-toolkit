# ForbidDescriptivePhpDocInTestClassRule

| Property | Value |
|----------|-------|
| Identifier | `customRules.testClassDescriptivePhpDoc` |
| Scope | Classes and methods in `Tests\Unit` / `Tests\Integration` |
| Configurable | Via `restrictedTestNamespacePrefixes` |

## What It Detects

Reports classes and methods in test classes that have descriptive prose in their PHPDoc. Only annotation-only PHPDoc (containing only `@` tags) is allowed.

```php
// ERROR: descriptive text on test class
/**
 * Tests for the UserService.
 */
class UserServiceTest extends TestCase {}

// ERROR: descriptive text on test method
/**
 * This test verifies that the user can log in.
 */
public function testUserCanLogIn(): void {}

// ERROR: descriptive text on setUp
/**
 * Clears environment variables before each test.
 */
protected function setUp(): void {}

// ERROR: mixed description and tags
/**
 * Verifies the calculation.
 * @dataProvider providerUserScenarios
 */
public function testCalculation(): void {}

// OK: annotation-only PHPDoc
/**
 * @extends RuleTestCase<SomeRule>
 */
class SomeRuleTest extends RuleTestCase {}

// OK: annotation-only PHPDoc
/**
 * @dataProvider providerUsers
 */
public function testUserAccess(User $user): void {}

// OK: no PHPDoc at all
public function testSimpleCase(): void {}
```

## Why This Is an Error

Test class and method names are the primary documentation. A well-named test like `testUserCanLogInWithValidCredentials` needs no additional description. When AI generates test code, it often adds redundant PHPDoc that restates what the name already says.

Descriptive PHPDoc creates maintenance burden — when the behavior changes, both the name and the PHPDoc must be updated. In practice, the PHPDoc drifts out of sync.

## How to Fix

1. Remove the descriptive text from the PHPDoc
2. If the PHPDoc has no remaining `@` tags, remove it entirely
3. If the name is not self-explanatory, improve the name

```php
// Bad: redundant description
/**
 * Verifies that creating a user with an invalid email throws an exception.
 */
public function testCreateUserWithInvalidEmail(): void {}

// Good: no PHPDoc needed — the method name says it all
public function testCreateUserWithInvalidEmailThrowsException(): void {}
```

```php
// Bad: description mixed with functional tag
/**
 * Tests various user scenarios.
 * @dataProvider providerUserScenarios
 */
public function testUserScenarios(string $input, string $expected): void {}

// Good: keep only the functional tag
/**
 * @dataProvider providerUserScenarios
 */
public function testUserScenarios(string $input, string $expected): void {}
```
