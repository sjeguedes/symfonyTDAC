<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

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
     * Check unauthorized user with login page redirection.
     *
     * @param KernelBrowser $client
     *
     * @return void
     */
    protected static function assertAccessIsDenied(KernelBrowser $client): void
    {
        static::assertEquals(Response::HTTP_FOUND, $client->getResponse()->getStatusCode());
        $crawler = $client->followRedirect();
        $buttonCrawlerNode = $crawler->selectButton('Se connecter');
        static::assertRegExp('/\/login_check/', $buttonCrawlerNode->form()->getUri());
    }

    /**
     * Login with a user token and session storage.
     *
     * @return UserInterface|User
     */
    protected function loginUser(): UserInterface
    {
        // Get a Session instance
        $session = static::$container->get('session');
        // Get a user from test database
        /* @var ObjectRepository $userRepository */
        $userRepository = static::$container->get('doctrine')->getRepository(User::class);
        // Retrieve the test user
        /** @var UserInterface $testUser */
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

        return $testUser;
    }

    /**
     * Free memory after each test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->client = null;
        static::ensureKernelShutdown();
    }
}