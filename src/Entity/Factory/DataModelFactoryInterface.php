<?php

declare(strict_types=1);

namespace App\Entity\Factory;

/**
 * Interface DataModelFactoryInterface
 *
 * Define a contract to create a model.
 */
interface DataModelFactoryInterface
{
    /**
     * Manage creation about a particular model instance.
     *
     * @param string $modelReference a Fully Qualified Class Name or simple label
     *
     * @return object the instance to produce
     */
    public function create(string $modelReference): object;
}
