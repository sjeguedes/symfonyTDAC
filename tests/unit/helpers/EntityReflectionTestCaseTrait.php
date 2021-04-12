<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helpers;

use App\Entity\Task;

/**
 * Trait EntityReflectionTestCaseTrait
 *
 * Add PHPUnit unit test case Task reflection helpers.
 */
trait EntityReflectionTestCaseTrait
{
    /**
     * Set entity id property by reflection to get a fake entity as it is provided from database.
     *
     * @param object $existingEntity
     * @param int    $expectedId
     *
     * @return object
     *
     * @throws \ReflectionException
     */
    private function setEntityIdByReflection(object $existingEntity, int $expectedId): object
    {
        $entityReflection = new \ReflectionObject($existingEntity);
        $idPropertyReflection = $entityReflection->getProperty('id');
        $idPropertyReflection->setAccessible(true);
        $idPropertyReflection->setValue($existingEntity, $expectedId);
        $idPropertyReflection->setAccessible(false);

        return $existingEntity;
    }
}