<?php

declare(strict_types=1);

namespace App\Tests\Unit\Helpers;

use App\Entity\Task;

/**
 * Trait TaskReflectionTestCaseTrait
 *
 * Add PHPUnit unit test case Task reflection helpers.
 */
trait TaskReflectionTestCaseTrait
{
    /**
     * Set Task id property by reflection to fake a entity provided from database.
     *
     * @param Task $existingTask
     *
     * @return Task
     *
     * @throws \ReflectionException
     */
    private function setTaskIdByReflection(Task $existingTask): Task
    {
        $taskReflection = new \ReflectionObject($existingTask);
        $idPropertyReflection = $taskReflection->getProperty('id');
        $idPropertyReflection->setAccessible(true);
        $idPropertyReflection->setValue($existingTask, 1);
        $idPropertyReflection->setAccessible(false);

        return $existingTask;
    }
}