# TestNamingConventionRule

| Property | Value |
|----------|-------|
| Identifiers | `customRules.testMethodNamingConvention`, `customRules.providerNamingConvention`, `customRules.testMethodProhibitedConstructorDestructor`, `customRules.publicMethodWithoutTest` |
| Scope | Test classes in restricted namespaces; source classes in `src/` |
| Configurable | Yes (namespace prefixes, path markers) |

## What It Detects

This rule enforces naming conventions for test methods and data providers in restricted test classes, prohibits testing constructors/destructors directly, and ensures every public method in source classes has a corresponding test method.

### 1. Test method naming (`customRules.testMethodNamingConvention`)

Test methods starting with `test` must follow PascalCase after the prefix: `test[MethodName]` or `test[MethodName][Behavior]`.

```php
// ERROR: The prefix "test" alone is not a valid name.
public function test(): void { ... }

// ERROR: After the "test" prefix, the next character must be an uppercase letter.
public function testsomething(): void { ... }

// ERROR: Same — underscore is not uppercase.
public function test_something(): void { ... }

// OK
public function testSomething(): void { ... }
public function testUserCanLogin(): void { ... }
```

### 2. Data provider naming (`customRules.providerNamingConvention`)

Data provider methods starting with `provider` must follow PascalCase after the prefix: `provider[TestCaseName]`.

```php
// ERROR: The prefix "provider" alone is not a valid name.
public static function provider(): array { ... }

// ERROR: After the "provider" prefix, the next character must be an uppercase letter.
public static function providerdata(): array { ... }

// ERROR: Same — underscore is not uppercase.
public static function provider_data(): array { ... }

// OK
public static function providerValidEmails(): array { ... }
public static function providerUserData(): array { ... }
```

### 3. Prohibited constructor/destructor tests (`customRules.testMethodProhibitedConstructorDestructor`)

Test methods whose name after `test` starts with `Construct` or `Destruct` are prohibited:

```php
// ERROR: Tests a constructor directly.
public function testConstruct(): void { ... }
public function testConstructor(): void { ... }
public function testConstructThrowsException(): void { ... }

// ERROR: Tests a destructor directly.
public function testDestruct(): void { ... }
public function testDestructor(): void { ... }
public function testDestructorIsCalled(): void { ... }

// OK — "Reconstruct" does not start with "Construct"
public function testReconstructData(): void { ... }
```

### 4. Public method test coverage (`customRules.publicMethodWithoutTest`)

Every public non-magic method in a source file (`src/`) must have at least one corresponding test method starting with `test[MethodName]` in the unit test file. Abstract methods, magic methods (`__construct`, `__toString`, etc.), and private/protected methods are excluded.

```php
// src/Service/UserService.php
class UserService
{
    public function create(User $user): void { ... }
    public function findById(int $id): ?User { ... }
    public function __construct(private Repository $repo) {}
    private function validate(User $user): void { ... }
}
```

```php
// tests/Unit/Service/UserServiceTest.php
class UserServiceTest extends TestCase
{
    public function testCreatePersistsUser(): void { ... }      // OK — covers create()
    // ERROR: Public method findById() has no corresponding test method
    //        starting with testFindById() in the unit test file.
}
```

## Why This Is an Error

### Inconsistent naming reduces readability

When test methods use inconsistent casing (`testsomething`, `test_something`), it becomes harder to scan test files and understand what each test covers. PascalCase after the `test` prefix (`testSomething`) creates a consistent, readable pattern across the entire test suite.

### Bare prefixes carry no information

A method named `test()` or `provider()` gives no indication of what it tests or what data it provides. Every test method and data provider should have a descriptive suffix that communicates its purpose.

### Constructor/destructor testing couples tests to implementation

Constructors and destructors are implementation details. Testing them directly (`testConstructor`, `testConstruct`) couples the test to the object's lifecycle rather than its behavior. When the constructor signature changes, these tests break even though the object's public behavior may be unchanged.

### Untested public methods are invisible risks

If a public method has no corresponding test, its behavior is unverified. The `test[MethodName]` naming convention makes it easy to see which methods are tested and which are not. When a test method must start with the source method name, missing coverage becomes immediately apparent.

### AI anti-pattern

AI code generators frequently create `testConstructor()` methods that simply verify an object can be instantiated — a test with no behavioral value. They also produce snake_case test names (`test_it_works`) from Python conventions or bare `test()` methods as placeholders. They also tend to skip writing tests for methods they consider "simple". This rule catches these patterns early.

## How to Fix

### Rename to PascalCase

```php
// Before
public function testsomething(): void { ... }
public function test_user_can_login(): void { ... }

// After
public function testSomething(): void { ... }
public function testUserCanLogin(): void { ... }
```

### Add descriptive suffixes

```php
// Before
public function test(): void { ... }
public static function provider(): array { ... }

// After
public function testUserIsCreated(): void { ... }
public static function providerValidEmails(): array { ... }
```

### Replace constructor tests with behavioral tests

```php
// Before — testing the constructor directly
public function testConstructor(): void
{
    $user = new User('Alice', 30);
    self::assertSame('Alice', $user->name());
    self::assertSame(30, $user->age());
}

// After — testing the behavior that the constructor enables
public function testNameReturnsGivenName(): void
{
    $user = new User('Alice', 30);
    self::assertSame('Alice', $user->name());
}

public function testAgeReturnsGivenAge(): void
{
    $user = new User('Alice', 30);
    self::assertSame(30, $user->age());
}
```

### Add tests for uncovered public methods

```php
// Before — findById() has no test
class UserServiceTest extends TestCase
{
    public function testCreatePersistsUser(): void { ... }
}

// After — every public method has at least one test
class UserServiceTest extends TestCase
{
    public function testCreatePersistsUser(): void { ... }

    public function testFindByIdReturnsUser(): void { ... }

    public function testFindByIdReturnsNullWhenNotFound(): void { ... }
}
```

## Configuration

Customize which namespaces are considered restricted and which path markers identify source/test directories:

```neon
parameters:
    customRules:
        restrictedTestNamespacePrefixes:
            - 'Tests\Unit'
            - 'Tests\Integration'
        srcMarker: '/src/'
        unitTestMarker: '/tests/Unit/'
```
