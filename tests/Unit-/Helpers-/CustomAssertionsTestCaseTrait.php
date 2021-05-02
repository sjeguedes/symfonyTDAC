<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

/**
 * Trait CustomAssertionsTestCaseTrait
 *
 * Add PHPUnit unit test case custom assertions.
 */
trait CustomAssertionsTestCaseTrait
{
    /**
     * Assert that an instance implements a particular interface.
     *
     * Please note that this is a custom assertion.
     *
     * @param string $expectedInterface
     * @param object $testObject
     * @param string $message
     *
     * @return void
     */
    private static function assertImplements(string $expectedInterface, object $testObject, string $message = ''): void
    {
        self::checkUnitTestCase();
        // If "false" is returned, an error happened with this native function.
        $interfacesNames = class_implements($testObject);
        $value = false !== $interfacesNames ? \in_array($expectedInterface, $interfacesNames) : false;
        self::assertThat($value, self::isTrue(), $message);
    }

    /**
     * Ensure all custom assertions are used in PHPUnit unit test case context.
     *
     * @return void
     */
    private static function checkUnitTestCase(): void
    {
        if (false === \in_array(TestCase::class, class_parents(__CLASS__))) {
            throw new \RuntimeException('Current class must extend PHPUnit "TestCase" to use this trait!');
        }
    }
}