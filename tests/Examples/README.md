# Example Tests

This directory contains example/demo tests that demonstrate different testing patterns and techniques using Codeception.

## Purpose

These tests serve as:
- **Learning examples** for writing new tests
- **Reference implementations** for common testing patterns
- **Documentation** of testing best practices

## Test Files

- **`BasicPluginTest.php`** - Basic assertions and PHP functionality testing
- **`SimpleTest.php`** - String and array operations testing
- **`ImageFunctionsTest.php`** - Data validation and URL testing patterns
- **`UtilityFunctionsTest.php`** - Version comparison and utility function testing

## Running Examples

```bash
# Run all example tests
lando php vendor/bin/codecept run Examples

# Run specific example test
lando php vendor/bin/codecept run Examples BasicPluginTest

# Run with debug output
lando php vendor/bin/codecept run Examples --debug
```

## Using as Templates

You can copy these examples to the `Unit/` directory and modify them for your actual plugin tests:

```bash
# Copy an example to Unit directory
cp tests/Examples/BasicPluginTest.php tests/Unit/MyActualTest.php

# Update the namespace from Tests\Examples to Tests\Unit
# Update the class name and add your actual tests
```

These examples are kept separate from actual unit tests to maintain a clean test organization.





