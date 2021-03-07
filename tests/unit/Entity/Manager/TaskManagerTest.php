<?php

declare(strict_types=1);

namespace App\Tests\unit\Entity\Manager;

use App\Entity\Manager\TaskManager;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class TaskManagerTest
 *
 * Manage unit tests for task manager.
 */
class TaskManagerTest extends TestCase
{
    /**
     * @var MockObject|EntityManagerInterface|null
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * @var MockObject|LoggerInterface|null
     */
    private ?LoggerInterface $logger;

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
        $this->entityManager = static::createMock(EntityManagerInterface::class);
        $this->logger = static::createMock(LoggerInterface::class);
        $this->taskManager = new TaskManager($this->entityManager, $this->logger);
    }

    /**
     * Check that "create" method will set (associate) a user as author.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testTaskCreationWillSetAUserAsAuthor(): void
    {
        $taskModel = new Task();
        // Authenticated user obtained from token storage is checked inside CreateTaskHandlerTest!
        $user = static::createMock(User::class);
        $user
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $this->taskManager->create($taskModel, $user);
        static::assertEquals(1, $taskModel->getAuthor()->getId());
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->entityManager = null;
        $this->logger = null;
        $this->taskManager = null;
    }
}