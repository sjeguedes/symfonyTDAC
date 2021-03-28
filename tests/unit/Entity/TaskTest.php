<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\Task;
use App\Tests\Unit\Helpers\EntityReflectionTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class TaskTest
 *
 * Manage unit tests for Task entity.
 */
class TaskTest extends TestCase
{
    use EntityReflectionTestCaseTrait;

    /**
     * @var Task|null
     */
    private ?Task $task;

    /**
     * Setup needed instance(s).
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->task = new Task();
    }

    /**
     * Check that a new task should not be marked as done by default with constructor.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testNewTaskShouldNotBeMarkedAsDone(): void
    {
        static::assertTrue(false === $this->task->isDone());
    }

    /**
     * Check that update date cannot be set later before creation date and throws an exception.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testTaskUpdateDateCannotBeSetBeforeCreation(): void
    {
        static::expectException(\LogicException::class);
        $this->task->setUpdatedAt(new \DateTimeImmutable('-1day'));
    }

    /**
     * Check that creation and update date are both the same on instantiation.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testTaskUpdateDateIsInitiallyEqualToCreation(): void
    {
        static::assertEquals($this->task->getCreatedAt(), $this->task->getUpdatedAt());
    }

    /**
     * Check that author cannot be modified (if task is already persisted).
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testTaskAuthorCannotBeModified(): void
    {
        $user = static::createMock(UserInterface::class);
        // Use a fake task object type here would be better than reflection!
        // "Simulate" an already persisted object by setting id
        /** @var object|Task $this->task */
        $this->task = $this->setEntityIdByReflection($this->task, 1);
        static::expectException(\RuntimeException::class);
        $this->task->setAuthor($user);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->task = null;
    }
}