<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Tests\Unit\Helpers\EntityReflectionTestCaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class UserTest
 *
 * Manage unit tests for User entity.
 */
class UserTest extends TestCase
{
    use EntityReflectionTestCaseTrait;

    /**
     * @var User|null
     */
    private ?User $user;

    /**
     * Setup needed instance(s).
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->user = new User();
    }

    /**
     * Check that a new user should have "ROLE_USER" role by default with constructor.
     *
     * @return void
     */
    public function testNewUserShouldHaveUserRoleByDefault(): void
    {
        static::assertSame(['ROLE_USER'], $this->user->getRoles());
    }

    /**
     * Check that update date cannot be set later before creation date and throws an exception.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testUserUpdateDateCannotBeSetBeforeCreation(): void
    {
        static::expectException(\LogicException::class);
        $this->user->setUpdatedAt(new \DateTimeImmutable('-1day'));
    }

    /**
     * Check that creation and update date are both the same on instantiation.
     *
     * @return void
     */
    public function testUserUpdateDateIsInitiallyEqualToCreation(): void
    {
        static::assertEquals($this->user->getCreatedAt(), $this->user->getUpdatedAt());
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->user = null;
    }
}