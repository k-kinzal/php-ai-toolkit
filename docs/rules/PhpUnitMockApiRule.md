# PhpUnitMockApiRule

| Property | Value |
|----------|-------|
| Identifiers | `customRules.testClassPhpUnitMockProhibitedApi`, `customRules.testClassPhpUnitMockRequiresInterface`, `customRules.testClassPhpUnitMockRequiresLiteralInterface`, `customRules.testClassPhpUnitMockProhibitedInstantiation` |
| Scope | All test classes (`Tests\` namespace) |
| Configurable | Yes (namespace prefixes) |

## What It Detects

This rule enforces interface-only mocking by restricting how PHPUnit's mock API is used.

### 1. Prohibited mock APIs (`customRules.testClassPhpUnitMockProhibitedApi`)

The following methods are always forbidden:

- `getMockBuilder()`
- `createPartialMock()`
- `createTestProxy()`
- `getMockForAbstractClass()`
- `getMockForTrait()`
- `getMockFromWsdl()`

```php
// ERROR: Use createMock(FooInterface::class) or createStub(FooInterface::class) instead of PHPUnit getMockBuilder().
$mock = $this->getMockBuilder(FooInterface::class)
    ->disableOriginalConstructor()
    ->getMock();

// ERROR: Use createMock(FooInterface::class) or createStub(FooInterface::class) instead of PHPUnit createPartialMock().
$mock = $this->createPartialMock(ConcreteService::class, ['process']);
```

### 2. Interface-only enforcement (`customRules.testClassPhpUnitMockRequiresInterface`)

`createMock()`, `createStub()`, `createConfiguredMock()`, and `createConfiguredStub()` must target an interface:

```php
// ERROR: Pass an interface class-string to PHPUnit createMock(); "App\Service\UserService" is not an interface.
$mock = $this->createMock(UserService::class);

// ERROR: Pass an interface class-string to PHPUnit createStub(); "App\Repository\OrderRepository" is not an interface.
$stub = $this->createStub(OrderRepository::class);

// OK
$mock = $this->createMock(UserServiceInterface::class);
$stub = $this->createStub(OrderRepositoryInterface::class);
```

### 3. Literal class-string required (`customRules.testClassPhpUnitMockRequiresLiteralInterface`)

The first argument must be a direct `::class` constant fetch, not a variable or string:

```php
// ERROR: Pass an interface class-string literal to PHPUnit createMock(), e.g. DependencyInterface::class.
$mock = $this->createMock($className);

// ERROR: also reported
$mock = $this->createMock('App\Service\FooInterface');

// OK
$mock = $this->createMock(FooInterface::class);
```

### 4. Prohibited direct instantiation (`customRules.testClassPhpUnitMockProhibitedInstantiation`)

Direct instantiation of `MockBuilder` and `MockGenerator` is forbidden:

```php
// ERROR: Use createMock(FooInterface::class) or createStub(FooInterface::class) instead of instantiating PHPUnit\Framework\MockObject\MockBuilder directly.
$builder = new MockBuilder($this, FooInterface::class);
```

## Why This Is an Error

### Concrete class mocking couples tests to implementations

When you mock a concrete class, PHPUnit creates a subclass that overrides its methods. This means your test depends on the class's constructor signature, method visibility, and inheritance chain. Any refactoring of the concrete class can break tests that never intended to test that class.

```php
// Fragile: breaks if UserService changes its constructor
$mock = $this->createMock(UserService::class);

// Stable: only depends on the interface contract
$mock = $this->createMock(UserServiceInterface::class);
```

### Partial mocks hide design problems

`createPartialMock()` and `getMockBuilder()` allow mocking only some methods while keeping real implementations for others. This typically indicates the class has too many responsibilities. Instead of partially mocking, redesign the class to follow the Single Responsibility Principle.

### Non-literal class strings prevent static verification

When the mock target is a variable or string literal, PHPStan cannot verify at analysis time that the target is an interface. This defeats the purpose of the rule.

### AI anti-pattern

AI code generators frequently mock concrete classes because they copy the class name directly from the production code. They also tend to use `getMockBuilder()` chains because training data contains older PHPUnit patterns. This rule forces the cleaner, modern API.

## How to Fix

### Replace concrete class mocking with interface mocking

If no interface exists, create one:

```php
// Before
class UserService
{
    public function findById(int $id): ?User { ... }
}

// Step 1: Extract interface
interface UserServiceInterface
{
    public function findById(int $id): ?User;
}

class UserService implements UserServiceInterface { ... }

// Step 2: Mock the interface
$mock = $this->createMock(UserServiceInterface::class);
```

### Replace getMockBuilder with createMock/createStub

```php
// Bad
$mock = $this->getMockBuilder(FooInterface::class)
    ->disableOriginalConstructor()
    ->getMock();
$mock->method('bar')->willReturn('baz');

// Good
$mock = $this->createStub(FooInterface::class);
$mock->method('bar')->willReturn('baz');
```

### Replace createPartialMock with proper design

```php
// Bad: partial mock indicates mixed responsibilities
$service = $this->createPartialMock(ReportService::class, ['fetchData']);
$service->method('fetchData')->willReturn([...]);
$result = $service->generateReport();

// Good: separate the responsibilities
$fetcher = $this->createStub(DataFetcherInterface::class);
$fetcher->method('fetchData')->willReturn([...]);

$service = new ReportService($fetcher);
$result = $service->generateReport();
```

### Use createMock vs createStub

- **`createStub()`**: When you only need to control return values (no expectation on call count)
- **`createMock()`**: When you need to verify that methods are called with specific arguments

```php
// Stub: only controls behavior
$repository = $this->createStub(UserRepositoryInterface::class);
$repository->method('findById')->willReturn(new User('Alice'));

// Mock: also verifies interactions
$logger = $this->createMock(LoggerInterface::class);
$logger->expects(self::once())
    ->method('info')
    ->with('User created: Alice');
```

## Configuration

Customize which namespaces are considered test classes:

```neon
parameters:
    customRules:
        testNamespacePrefixes:
            - 'Tests'
```
