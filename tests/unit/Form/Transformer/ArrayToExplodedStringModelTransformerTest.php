<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Transformer;

use App\Form\Transformer\ArrayToExplodedStringModelTransformer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

/**
 * Class ArrayToExplodedStringModelTransformerTest
 *
 * Manage unit tests for form actions which use ArrayToExplodedStringModelTransformer transformer.
 */
class ArrayToExplodedStringModelTransformerTest extends TestCase
{
    /**
     * @var DataTransformerInterface|null
     */
    private ?DataTransformerInterface $modelTransformer;

    /**
     * Setup needed instance(s).
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->modelTransformer = new ArrayToExplodedStringModelTransformer();
    }

    /**
     * Provide a set of wrong value types to check transformation failure.
     *
     * @return array
     */
    public function provideWrongTransformationValueTypes(): array
    {
        // Expects an array
        return [
            'Uses bool type'   => [true],
            'Uses int type'    => [1],
            'Uses string type' => [''],
            'Uses object type' => [new \StdClass()]
        ];
    }

    /**
     * Provide a set of wrong value types to check reverse transformation failure.
     *
     * @return array
     */
    public function provideWrongReverseTransformationValueTypes(): array
    {
        // Expects a string
        return [
            'Uses bool type'   => [true],
            'Uses int type'    => [1],
            'Uses array type'  => [[]],
            'Uses object type' => [new \StdClass()]
        ];
    }

    /**
     * Check that model transformation expects an array.
     *
     * @dataProvider provideWrongTransformationValueTypes
     *
     * @param mixed $value
     *
     * @return void
     */
    public function testTransformationFailsWhenValueIsNotOfArrayType($value): void
    {
        static::expectException(TransformationFailedException::class);
        static::expectExceptionMessage('An array is expected to obtain a string!');
        $this->modelTransformer->transform($value);

    }

    /**
     * Check that model transformation returns an empty string with "null" or empty array as value.
     *
     * @return void
     */
    public function testTransformationReturnsAnEmptyStringWithNullOrEmptyArrayValue(): void
    {
        $result = $this->modelTransformer->transform(null);
        static::assertSame('', $result);
        $result = $this->modelTransformer->transform([]);
        static::assertSame('', $result);
    }

    /**
     * Check that model transformation returns a delimited string with array as value.
     *
     * @return void
     */
    public function testTransformationIsOkWhenValueIsAnArray(): void
    {
        $result = $this->modelTransformer->transform(['string1', 'string2', 'string3']);
        // Get a delimited string
        static::assertSame('string1, string2, string3', $result);
        // Get a trimmed and cleaned delimited string provided by array values
        $result = $this->modelTransformer->transform(['  string1', '  string2 ', ' string3 ']);
        static::assertSame('string1, string2, string3', $result);
    }

    /**
     * Check that model reverse transformation expects a string.
     *
     * @dataProvider provideWrongReverseTransformationValueTypes
     *
     * @param mixed $value
     *
     * @return void
     */
    public function testReverseTransformationFailsWhenValueIsNotOfStringType($value): void
    {
        static::expectException(TransformationFailedException::class);
        static::expectExceptionMessage('A string is expected to obtain an array!');
        $this->modelTransformer->reverseTransform($value);

    }

    /**
     * Check that model reverse transformation returns an empty array with "null" or empty (cleaned) string as value.
     *
     * @return void
     */
    public function testReverseTransformationReturnsAnEmptyArrayWithNullOrEmptyStringValue(): void
    {
        $result = $this->modelTransformer->reverseTransform(null);
        static::assertSame([], $result);
        $result = $this->modelTransformer->reverseTransform('');
        static::assertSame([], $result);
        $result = $this->modelTransformer->reverseTransform('  ,   ,  ,  ');
        static::assertSame([], $result);
    }

    /**
     * Check that model reverse transformation returns an array of string as value.
     *
     * @return void
     */
    public function testReverseTransformationIsOkWhenValueIsAString(): void
    {
        $result = $this->modelTransformer->reverseTransform('string1, string2, string3');
        // Get a delimited string
        static::assertSame(['string1', 'string2' , 'string3'], $result);
        // Get an array based on trimmed and cleaned delimited string
        $result = $this->modelTransformer->reverseTransform('  string1 ,  string2 ,    string3 ');
        static::assertSame(['string1', 'string2' , 'string3'], $result);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->modelTransformer = null;
    }
}