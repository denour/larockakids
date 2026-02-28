---
name: tdd-zombie
description: Step-by-step guide to develop features using TDD with the ZOMBIE methodology (Zero, One, Many, Boundaries, Interface, Exceptional). Use this skill when the user asks to create tests, do TDD, or develop a feature with tests.
argument-hint: [feature-description]
allowed-tools: Read, Grep, Glob, Bash, Edit, Write
---

# TDD with ZOMBIE Methodology

Guide the user step-by-step through the TDD process using James Grenning's ZOMBIE methodology. Each step follows the **Red -> Green -> Refactor** cycle.

**CRITICAL: All user-facing output (messages, phase headers, status updates, explanations) MUST be in Spanish. Only code, test names, and variable names should be in English.**

## What is ZOMBIE

| Letter | Phase | Description |
|--------|-------|-------------|
| **Z** | Zero | Simplest case: empty collections, null values, initial state |
| **O** | One | A single element, the base functional case |
| **M** | Many (More complex) | Multiple elements, interactions between them |
| **B** | Boundaries | Edge values, extremes, boundary conditions |
| **I** | Interface | Validate the public interface is correct and coherent |
| **E** | Exceptional | Errors, exceptions, failure cases |

## Instructions

### 1. Understand the feature
- Read the context provided by the user in `$ARGUMENTS`
- Identify the model, service, or component involved
- Review existing related files (models, services, tests)
- Review existing factories for the involved models
- Check sibling test files for project conventions

### 2. Create the test file
- Use `php artisan make:test --phpunit <TestName>` to create the test
- Test should be **Feature** type unless the user requests a unit test
- Follow the existing test conventions in the project

### 3. Follow ZOMBIE in strict order

For **each phase** of ZOMBIE:

#### RED step
1. Write ONE failing test (the minimum necessary for the current phase)
2. Run the test: `php artisan test --filter=testMethodName`
3. Confirm it fails for the correct reason

#### GREEN step
1. Write the MINIMUM code to make the test pass
2. Do not write extra code — only what is necessary
3. Run the test to confirm it passes

#### REFACTOR step
1. Improve the code without changing behavior
2. Remove duplication
3. Run tests to confirm they still pass

### 4. Detailed flow per phase

#### Z - Zero (Start here)
Tests for the initial/empty state:
- Empty collection returns empty
- Counter starts at zero
- New model has default values
- Method without parameters returns expected value

```php
/** @test */
public function test_returns_empty_collection_when_no_records(): void
{
    // Arrange - nothing

    // Act
    $result = MyService::getItems();

    // Assert
    $this->assertCount(0, $result);
}
```

#### O - One (Single case)
Tests for a single element:
- Create one record and verify it exists
- Process one item and verify result
- One input produces one expected output

```php
/** @test */
public function test_returns_one_item_when_one_exists(): void
{
    // Arrange
    $item = Item::factory()->create();

    // Act
    $result = MyService::getItems();

    // Assert
    $this->assertCount(1, $result);
    $this->assertTrue($result->contains($item));
}
```

#### M - Many (Multiple cases)
Tests for multiple elements:
- Multiple records, correct ordering
- Filters with several results
- Relationships between multiple entities
- Aggregations and grouping behavior

#### B - Boundaries (Limits)
Tests for boundary values:
- Empty strings vs null
- Maximum/minimum values
- Pagination on the last page
- Date boundaries (start/end of day, month, year)
- Off-by-one scenarios

#### I - Interface (Public API)
Validate the public API:
- Optional parameters work correctly
- Return types are correct
- Chainable methods work
- Response structure is consistent
- Method signatures make sense

#### E - Exceptional (Exceptions)
Tests for errors and exceptions:
- Model not found throws exception
- Validation rejects invalid data
- Denied permissions return 403
- Corrupt data is handled gracefully
- Required fields missing triggers validation error

```php
/** @test */
public function test_throws_exception_when_item_not_found(): void
{
    $this->expectException(ModelNotFoundException::class);

    MyService::getItem(999);
}
```

### 5. Important rules

- **Never write more than one test at a time** without running it first
- **Never write production code** without a test that demands it
- **Report each phase** to the user in Spanish: "Estamos en fase Z (Zero) - Test RED"
- **Use existing factories** from the project to create test data
- **Follow project conventions**: check sibling tests for structure
- **Run `vendor/bin/pint --dirty`** at the end to format the code
- When all phases are done, run all related tests together
- Ask the user if they want to run the full test suite

### 6. Communication format (in Spanish)

When starting each phase, display:

```
## ZOMBIE - Fase [LETTER]: [Name in Spanish]
Estado: RED | GREEN | REFACTOR
Test: [test method name]
```

When completing each phase:

```
Fase [LETTER] completada. Tests pasando: X/X
Siguiente fase: [LETTER] - [Name in Spanish]
```

Phase names in Spanish:
- Z: Zero (Cero)
- O: One (Uno)
- M: Many (Muchos)
- B: Boundaries (Limites)
- I: Interface (Interfaz)
- E: Exceptional (Excepciones)

### 7. Project context

This is a Laravel 12 project with Filament v3, PHPUnit v11. Tests use:
- `php artisan make:test --phpunit` to create tests
- `php artisan test --filter=` to run specific tests
- Factories to create test data
- `RefreshDatabase` trait for database tests
- `vendor/bin/pint --dirty` to format at the end
