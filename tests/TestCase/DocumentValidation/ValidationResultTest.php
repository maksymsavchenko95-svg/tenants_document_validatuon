<?php
declare(strict_types=1);

namespace App\Test\TestCase\DocumentValidation;

use App\DocumentValidation\ValidationResult;
use PHPUnit\Framework\TestCase;

final class ValidationResultTest extends TestCase
{
    public function testValidHasNoErrors(): void
    {
        $result = ValidationResult::valid();

        $this->assertTrue($result->isValid);
        $this->assertSame([], $result->errors);
    }

    public function testInvalidCarriesErrorsAndIsDerived(): void
    {
        $result = ValidationResult::invalid('a', 'b');

        $this->assertFalse($result->isValid);
        $this->assertSame(['a', 'b'], $result->errors);
    }

    public function testConstructorDerivesIsValidFromErrors(): void
    {
        $this->assertTrue((new ValidationResult([]))->isValid);
        $this->assertFalse((new ValidationResult(['x']))->isValid);
    }

    public function testMergeReturnsNewInstanceAndPreservesOrder(): void
    {
        $a = ValidationResult::invalid('first');
        $b = ValidationResult::invalid('second', 'third');

        $merged = $a->merge($b);

        $this->assertNotSame($a, $merged);
        $this->assertNotSame($b, $merged);
        $this->assertSame(['first', 'second', 'third'], $merged->errors);
        $this->assertSame(['first'], $a->errors, 'operands are not mutated');
    }

    public function testMergingTwoValidResultsIsValid(): void
    {
        $this->assertTrue(ValidationResult::valid()->merge(ValidationResult::valid())->isValid);
    }

    public function testToArray(): void
    {
        $this->assertSame(
            ['isValid' => false, 'errors' => ['boom']],
            ValidationResult::invalid('boom')->toArray(),
        );
    }
}
