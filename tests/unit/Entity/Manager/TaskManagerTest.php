<?php

declare(strict_types=1);

namespace App\Tests\unit\Entity\Manager;

use App\Entity\Manager\TaskManager;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * Class TaskManagerTest
 *
 * Manage unit tests for task manager.
 */
class TaskManagerTest extends TestCase
{
    /**
     * @var TaskManager|null
     */
    private ?TaskManager $taskManager;

    /**
     * Setup needed instance(s).
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        $entityManager = static::createMock(EntityManagerInterface::class);
        $this->taskManager = new TaskManager($entityManager);
    }

    /**
     * Check that "create" method will set (associate) a user as author.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testCreateMethodWillSetAUserAsAuthor(): void
    {
        $taskModel = new Task();
        $authenticatedUser = static::createMock(User::class);
        $authenticatedUser
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->taskManager->create($taskModel, $authenticatedUser);
        static::assertEquals(1, $taskModel->getAuthor()->getId());
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->taskManager = null;
    }
}