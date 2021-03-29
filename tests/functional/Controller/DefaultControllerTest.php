<?php

declare(strict_types=1);

namespace Tests\Functional\Controller;

use App\Tests\Functional\Controller\Helpers\AbstractControllerWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DefaultControllerTest
 *
 * Define functional tests.
 */
class DefaultControllerTest extends AbstractControllerWebTestCase
{
    /**
     * Check that homepage is accessible only with authentication
     * and check it is correctly shown.
     *
     * @return void
     */
    public function testUserShouldAuthenticateInOrderToAccessIndex(): void
    {
        $this->loginUser();
        $this->client->request('GET', '/');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this-> assertSelectorTextContains(
            '.container h1',
            'Bienvenue sur Todo List, l\'application vous permettant de gérer l\'ensemble de vos tâches sans effort !'
        );
    }
}
