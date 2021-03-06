<?php

declare(strict_types=1);

namespace App\Form\Handler;

/**
 * Interface FormValidationStateInterface
 *
 * * Define a contract to implement a form validation successful state.
 */
interface FormValidationStateInterface
{
    /**
     * Manage a callback to execute expected actions depending on form validation success.
     *
     * @param array $data           a set of needed data to perform expected action(s)
     * @param bool  $isSuccess|null a state in order to determine if form is valid.
     *
     * @return bool a state to be informed about action(s) correct execution
     */
    public function execute(array $data = [], bool $isSuccess = null): bool;
}
