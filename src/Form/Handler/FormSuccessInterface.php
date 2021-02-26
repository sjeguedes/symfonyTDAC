<?php

declare(strict_types=1);

namespace App\Form\Handler;

/**
 * Interface FormSuccessInterface
 *
 * * Define a contract to implement a form validation successful state.
 */
interface FormSuccessInterface
{
    /**
     * Manage a callback to execute expected actions depending on form validation success.
     *
     * @param array $data
     */
    public function executeOnSuccess(array $data = []): void;
}