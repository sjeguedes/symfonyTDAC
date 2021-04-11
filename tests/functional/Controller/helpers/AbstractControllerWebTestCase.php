<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Helpers;

use App\Entity\User;
use Doctrine\Common\EventManager;
use Doctrine\ORM\Events;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class AbstractControllerWebTestCase
 *
 * Manage all functional tests common actions as parent class.
 */
abstract class AbstractControllerWebTestCase extends WebTestCase
{
    /**
     * @var KernelInterface|null
     */
    protected static ?KernelInterface $kernel = null;

    /**
     * @var KernelBrowser|null
     */
    protected ?KernelBrowser $client;

    /**
     * Initialize all needed instances before each test.
     *
     * @return void
     */
    public function setUp(): void
    {
        static::$kernel = static::bootKernel();
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
     * Create a Doctrine listener instance which throws an ORM exception.
     *
     * @param string $eventName
     * @return object
     */
    private function getTestDoctrineListenerWhichThrowsORMException(string $eventName): object
    {
        return new class() {
            /**
             * Define Doctrine callbacks to use.
             */
            private const CALLBACKS = [
                Events::postPersist,
                Events::postUpdate,
                Events::postRemove,
                Events::onFlush
            ];

            /**
             * Throw an exception on "$methodName" operation with dynamic call.
             *
             * @param string $methodName
             * @param array  $args
             *
             * @return void
             */
            public function __call(string $methodName, array $args): void
            {
                if (!\in_array($methodName, self::CALLBACKS)) {
                    throw new \BadMethodCallException('Doctrine callback is unknown!');
                }
                // Execute callback automatically: variable "args" is unnecessary at this time!)
                (function (...$args): void {
                    throw new ORMException();
                })();
            }
        };
    }

    /**
     * Login with a user token and session storage.
     *
     * @return UserInterface|User
     */
    protected function loginUser(): UserInterface
    {
        // Get a Session instance
        $session = static::$kernel->getContainer()->get('session');
        // Get a user from test database
        /* @var ObjectRepository $userRepository */
        $userRepository = static::$kernel->getContainer()->get('doctrine')->getRepository(User::class);
        // Retrieve the test user
        /** @var UserInterface $testUser */
        $testUser = $userRepository->findOneBy(['email' => 'marie.lambert@yahoo.fr']);
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
     * Make entity manager throw exception on database operations.
     *
     * Please note that these method simulates an ORM issue in order to check UX information message.
     *
     * @param string $eventName
     *
     * @return void
     */
    protected function makeEntityManagerThrowExceptionOnORMOperations(string $eventName): void
    {
        // IMPORTANT: keep the same kernel during request to avoid loss of added listener
        $this->client->disableReboot();
        /** @var EventManager $eventManager */
        $eventManager = static::$container->get('doctrine.dbal.event_manager');
        // Define and add a Doctrine listener with anonymous class instance in order to throw ORM exception
        $eventManager->addEventListener(
            $eventName,
            $this->getTestDoctrineListenerWhichThrowsORMException($eventName)
        );
    }

    /**
     * Free memory after each test.
     *
     * @return void
     */
    public function tearDown(): void
    {
        static::ensureKernelShutdown();
        static::$kernel = null;
        $this->client = null;
    }
}