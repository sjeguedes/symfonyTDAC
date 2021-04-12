<?php

declare(strict_types=1);

namespace App\Form\Handler;

/**
 * Interface FormHandlerInterface
 *
 * Define a contract to implement a form handler.
 *
 * Methods calls must respect this order: 1) process, 2) isSuccess
 */
interface FormHandlerInterface
{
    /**
     * Check if form processing is a success.
     *
     * Please note that use of a particular property can ease getting success state.
     *
     * @return bool
     */
    public function isSuccess(): bool;

    /**
     * Manage form data validation by creating a corresponding form instance internally.
     *
     * Please note that this method is used to determine if form is valid (success) or not (failure).
     *
     * @param object $request    an instance in order to represent the request with needed data.
     * @param array  $data       an array of necessary data to work with (form type, data model, entity, ...)
     * @param array $formOptions an array of custom data to merge with existing form options
     *
     * @return object|null the created form instance to use it later
     */
    public function process(object $request, array $data = [], array $formOptions = []): ?object;
}