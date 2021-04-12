<?php

declare(strict_types=1);

namespace App\Tests\Integration\Repository;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class UserRepositoryTest
 *
 * Manage integration tests for user repository (entity data layer).
 */
class UserRepositoryTest extends KernelTestCase
{
    /**
     * @var KernelInterface|null
     */
    protected static ?KernelInterface $kernel = null;

    /**
     * @var UserRepository|null
     */
    private ?UserRepository $userRepository;

    /**
     * Setup needed instance(s).
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        static::$kernel = static::bootKernel();
        // Access user repository private service using "static::$container"
        $this->userRepository = static::$container->get(UserRepository::class);
    }

    /**
     * Check that user list scalar data structure is same as expected.
     *
     * @return void
     */
    public function testUserListScalarDataStructureIsSameAsExpected(): void
    {
        $usersData = $this->userRepository->findList();
        // Check data structure
        static::assertArrayHasKey('id', $usersData[0]);
        static::assertArrayHasKey('username', $usersData[0]);
        static::assertArrayHasKey('email', $usersData[0]);
        static::assertCount(3, $usersData[0]);
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
        $this->userRepository = null;
        parent::tearDown();
    }
}