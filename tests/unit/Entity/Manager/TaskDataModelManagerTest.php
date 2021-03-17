<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Manager;

use App\Entity\Manager\TaskDataModelManager;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class TaskDataModelManagerTest
 *
 * Manage unit tests for task manager.
 */
class TaskDataModelManagerTest extends TestCase
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
     * @var TaskDataModelManager|null
     */
    private ?TaskDataModelManager $taskDataModelManager;

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
        $this->taskDataModelManager = new TaskDataModelManager($this->entityManager, $this->logger);
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
        $this->taskDataModelManager->create($taskModel, $user);
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
        $this->taskDataModelManager->update($taskModel, $authenticatedUser);
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
        $previousDateOfUpdate = $taskModel->getUpdatedAt();
        // Authenticated user obtained from token storage is checked inside CreateTaskHandlerTest!
        $authenticatedUser = static::createMock(User::class);
        $authenticatedUser
            ->expects($this->any())
            ->method('getId')
            ->willReturn(2);
        // Set author (permitted without id set) first, which will fake a task creation result.
        $this->taskDataModelManager->update($taskModel, $authenticatedUser);
        // Ensure task date of update is set
        static::assertTrue($previousDateOfUpdate < $taskModel->getUpdatedAt());
    }

    /**
     * Check that "toggle" method will inverse "task isDone" property.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testTaskToggleWillInverseIsDoneState(): void
    {
        // "IsDone" is set to false by default in constructor.
        $taskModel = new Task();
        // Inverse two times "isDone" state in order to expect a true toggle feature
        $this->taskDataModelManager->toggle($taskModel);
        static::assertTrue($taskModel->isDone());
        $this->taskDataModelManager->toggle($taskModel);
        static::assertFalse($taskModel->isDone());
    }

    /**
     * Check that "toggle" method will modify a date of update.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testTaskToggleIsCorrectlyTraced(): void
    {
        // Dates of creation and update are set in constructor automatically with the same value.
        $taskModel = new Task();
        $previousDateOfUpdate = $taskModel->getUpdatedAt();
        $this->taskDataModelManager->toggle($taskModel);
        // Ensure task date of update is set
        static::assertTrue($previousDateOfUpdate < $taskModel->getUpdatedAt());
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
        $this->taskDataModelManager = null;
    }
}