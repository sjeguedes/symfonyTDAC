<?php

declare(strict_types=1);

namespace App\View\Builder;

/**
 * Interface DataModelFactoryInterface
 *
 * Define a contract to create a view model to present data in a template.
 */
interface ViewModelBuilderInterface
{
    /**
     * Manage creation about a particular view model instance.
     *
     * @param string|null $viewReference
     * @param array       $mergedData
     *
     * @return object
     */
    public function create(string $viewReference = null, array $mergedData = []): object;
}
