<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * Class AbstractControllerTest
 *
 * Manage all tests common actions as parent class.
 */
abstract class AbstractControllerTest extends WebTestCase
{
    /**
     * @var KernelBrowser|null
     */
    protected ?KernelBrowser $client = null;

    /**
     * Initialize all needed instances before each test.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->client = static::createClient();
    }

    /**
     * Login with a user token and session storage.
     *
     * @return void
     */
    protected function loginUser(): void
    {
        // Get a Session instance
        $session = static::$container->get('session');
        // Get a user from test database
        /* @var ObjectRepository $userRepository */
        $userRepository = static::$container->get('doctrine')->getRepository(User::class);
        // Retrieve the test user
        $testUser = $userRepository->findOneBy(['email' => 'etienne.nguyen@tele2.fr']);
        // Define the context which defaults to the firewall name.
        $firewallName = 'main';
        $firewallContext = 'main';
        // Get a user token to save in session
        $token = new UsernamePasswordToken($testUser, null, $firewallName, ['ROLE_USER']);
        $session->set('_security_'.$firewallContext, serialize($token));
        $session->save();
        // Get a cookie to use with HTTP client
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    /**
     * Free memory after each test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->client = null;
    }
}