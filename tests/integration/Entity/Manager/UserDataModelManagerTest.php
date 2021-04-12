<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity\Manager;

use App\Entity\Manager\UserDataModelManager;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class UserDataModelManagerTest
 *
 * Manage integration tests for user data model manager.
 */
class UserDataModelManagerTest extends KernelTestCase
{
    /**
     * @var KernelInterface|null
     */
    protected static ?KernelInterface $kernel = null;

    /**
     * @var EntityManagerInterface|null
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * @var LoggerInterface|null
     */
    private ?LoggerInterface $logger;

    /**
     * @var UserDataModelManager|null
     */
    private ?UserDataModelManager $userDataModelManager;

    /**
     * @var UserPasswordEncoderInterface|null
     */
    private ?UserPasswordEncoderInterface $userPasswordEncoder;

    /**
     * Setup needed instance(s).
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::$kernel = static::bootKernel();
        // Get user password encoder private service
        $this->userPasswordEncoder = static::$container->get('security.user_password_encoder.generic');
        // Access entity manager public service using the kernel
        $this->entityManager = static::$kernel->getContainer()->get('doctrine')->getManager();
        // Access logger private service using "static::$container"
        $this->logger = static::$container->get('logger');
        // Set user data model manager instance
        $this->userDataModelManager = new UserDataModelManager(
            $this->entityManager,
            $this->logger
        );
    }

    /**
     * Create and save a new user in database.
     *
     * @return array an array with data model and new created user
     *
     * @throws \Exception
     */
    private function createUserInDatabase(): array
    {
        $userDataModel = (new User())
            ->setUsername('utilisateur')
            ->setEmail('utilisateur@test.fr')
            ->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $encodedPassword = $this->userPasswordEncoder->encodePassword($userDataModel, 'password_1A$');
        // Password encoded value is set in method!
        $this->userDataModelManager->create($userDataModel, $encodedPassword);
        $newUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $userDataModel->getEmail()]);
        return ['dataModel' => $userDataModel, 'user' => $newUser];
    }

    /**
     * Check that "create" method will add a user as expected.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testUserCreation(): void
    {
        // Call "create" method before to get a new fresh user
        $data = $this->createUserInDatabase();
        static::assertEquals($data['user'], $data['dataModel']);
    }

    /**
     * Check that "update" method will correctly modify a user.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testUserUpdate(): void
    {
        // Call "create" method before to get a new fresh user
        $data = $this->createUserInDatabase();
        $data['user']
            ->setUsername('utilisateur modifié')
            ->setEmail('utilisateur-modifie@test.fr')
            ->setRoles(['ROLE_USER']);
        $encodedPassword = $this->userPasswordEncoder->encodePassword($data['user'], 'password_2B$');
        // Password encoded value is set in method!
        $this->userDataModelManager->update($data['user'], $encodedPassword);
        $updatedUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $data['user']->getEmail()]);
        // Ensure expected changes was made
        static::assertSame($data['user']->getUsername(), $updatedUser->getUsername());
        static::assertSame($data['user']->getRoles(), $updatedUser->getRoles());
        static::assertSame($data['user']->getPassword(), $updatedUser->getPassword());
    }

    /**
     * Check that "update" method will set a date of update.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testUserUpdateIsCorrectlyTraced(): void
    {
        // Dates of creation and update are set in constructor automatically with the same value.
        // Call "create" method before to get a new fresh user
        $data = $this->createUserInDatabase();
        $previousDateOfUpdate = $data['user']->getUpdatedAt();
        $encodedPassword = $this->userPasswordEncoder->encodePassword($data['user'], 'password_2B$');
        // Password encoded value is set in method!
        $this->userDataModelManager->update($data['user'], $encodedPassword);
        $updatedUser = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['email' => $data['user']->getEmail()]);
        // Ensure user date of update is "set" (more exactly modified)
        static::assertTrue($previousDateOfUpdate < $updatedUser->getUpdatedAt());
    }

    /**
     * Check that "delete" method will remove User instance correctly.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testUserDeletionIsCorrectlyDone(): void
    {
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var ObjectRepository $userRepository */
        $previousUserList = $userRepository->findAll();
        // Remove first user in list
        $this->userDataModelManager->delete($previousUserList[0]);
        $nextUserList = $userRepository->findAll();
        static::assertSame(\count($previousUserList) - 1, \count($nextUserList));
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        static::ensureKernelShutdown();
        static::$kernel = null;
        $this->userPasswordEncoder = null;
        $this->entityManager->close();
        $this->entityManager = null;
        $this->logger = null;
        $this->userDataModelManager = null;
        parent::tearDown();
    }
}