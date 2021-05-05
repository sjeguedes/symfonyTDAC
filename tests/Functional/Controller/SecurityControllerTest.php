<?php

declare(strict_types=1);

namespace Tests\Functional\Controller;

use App\Tests\Functional\Controller\Helpers\AbstractControllerWebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class SecurityControllerTest
 *
 * Define functional tests for SecurityController.
 */
class SecurityControllerTest extends AbstractControllerWebTestCase
{
    /**
     * Check that login page is accessible without authentication
     * and check it is correctly shown.
     *
     * @return void
     */
    public function testUserCanAccessLoginPageWithoutAuthentication(): void
    {
        $this->client->request('GET', '/login');
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertSelectorTextContains(
            '.container h1',
            'S\'authentifier pour accéder à l\'application'
        );
    }

    /**
     * Check that authenticated user is redirected to homepage if he tries to access login page
     * and check it is correctly shown.
     *
     * @return void
     */
    public function testAuthenticatedUserIsRedirectedFromLoginToIndex(): void
    {
        $this->loginUser();
        $this->client->request('GET', '/login');
        $this->assertEquals(Response::HTTP_FOUND, $this->client->getResponse()->getStatusCode());
        $this->client->followRedirect();
        $this->assertSelectorTextContains(
            '.container h1',
            'Bienvenue sur Todo List, l\'application vous permettant de gérer l\'ensemble de vos tâches sans effort !'
        );
    }
}
