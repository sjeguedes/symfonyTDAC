<?php

declare(strict_types=1);

namespace App\Entity\Manager;

/**
 * Interface ModelManagerInterface
 *
 * Define a contract to implement a model manager.
 */
interface ModelManagerInterface
{
    /**
     * Manage data saving from model to database.
     *
     * @param object|null $dataModel the model or null if this operation does not need the data
     *
     * @return bool
     */
    public function saveData(object $dataModel = null): bool;
}