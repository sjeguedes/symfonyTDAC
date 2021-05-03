<?php

declare(strict_types=1);

namespace App\Entity\Manager;

/**
 * Interface ModelManagerInterface
 *
 * Define a contract to implement a model manager.
 */
interface DataModelManagerInterface
{
    /**
     * Get a persistence service layer instance to ease storage in database
     * once operations are made on model.
     *
     * @return object
     */
    public function getPersistenceLayer(): object;
}
