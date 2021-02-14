<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * Class TaskControllerTest
 *
 * Define functional tests for TaskController.
 */
class TaskControllerTest extends AbstractControllerTest
{
    /**
     * Check that a user should authenticate to be able to create a task.
     *
     * @return void
     */
    public function testUserShouldAuthenticateInOrderToCreateATask(): void
    {
        $this->loginUser();
        $this->client->request('GET', '/tasks/create');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
    }
}