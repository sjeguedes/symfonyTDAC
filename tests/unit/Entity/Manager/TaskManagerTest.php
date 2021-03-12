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
     * Check that "update" method will set (associate) a user as last editor.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testTaskUpdateWillSetAUserAsLastEditor(): void
    {
        $taskModel = new Task();
        // Authenticated user obtained from token storage is checked inside CreateTaskHandlerTest!
        $author = static::createMock(User::class);
        $authenticatedUser = static::createMock(User::class);
        $author
            ->expects($this->any())
            ->method('getId')
            ->willReturn(1);
        $authenticatedUser
            ->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        // Set author (permitted without id set) first, which will fake a task creation result.
        $taskModel->setAuthor($author);
        $this->taskManager->update($taskModel, $authenticatedUser);
        // Ensure no change was made on author and authenticated user is set as last editor
        static::assertEquals(1, $taskModel->getAuthor()->getId());
        static::assertEquals(2, $taskModel->getLastEditor()->getId());
    }

    /**
     * Check that "update" method will set a date of update.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testTaskUpdateIsCorrectlyTraced(): void
    {
        // Dates of creation and update are set in constructor automatically with the same value.
        $taskModel = new Task();
        // Authenticated user obtained from token storage is checked inside CreateTaskHandlerTest!
        $authenticatedUser = static::createMock(User::class);
        $authenticatedUser
            ->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        // Set author (permitted without id set) first, which will fake a task creation result.
        $this->taskManager->update($taskModel, $authenticatedUser);
        // Ensure task date of update is set
        static::assertTrue($taskModel->getUpdatedAt() !== $taskModel->getCreatedAt());
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