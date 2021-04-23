<?php

declare(strict_types=1);

namespace App\Tests\Integration\Entity\Manager;

use App\Entity\Manager\TaskDataModelManager;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class TaskDataModelManagerTest
 *
 * Manage integration tests for task data model manager.
 */
class TaskDataModelManagerTest extends KernelTestCase
{
    /**
     * @var EntityManagerInterface|null
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * @var LoggerInterface|null
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
     */
    public function setUp(): void
    {
        parent::setUp();
        static::$kernel = static::bootKernel();
        // Access entity manager public service using the kernel
        $this->entityManager = static::$kernel->getContainer()->get('doctrine')->getManager();
        /// Access logger private service using "static::$container"
        $this->logger = static::$container->get('logger');
        // Set task data model manager instance
        $this->taskDataModelManager = new TaskDataModelManager(
            $this->entityManager,
            $this->logger
        );
    }

    /**
     * Create and save a new task in database.
     *
     * @return array an array with data model and new created task
     *
     * @throws \Exception
     */
    private function createTaskInDatabase(): array
    {
        $taskDataModel = (new Task())
            ->setTitle('Titre de nouvelle tâche ' . uniqid())
            ->setContent('Description de nouvelle tâche');
        // Get a user with id "1" to be task author
        /** @var UserInterface $authenticatedUser */
        $authenticatedUser = $this->entityManager->getRepository(User::class)->find(1);
        $this->taskDataModelManager->create($taskDataModel, $authenticatedUser);
        $newTask = $this->entityManager
            ->getRepository(Task::class)
            ->findOneBy(['title' => $taskDataModel->getTitle()]);

        return ['dataModel' => $taskDataModel, 'newTask' => $newTask];
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
        // Call "create" method
        $data = $this->createTaskInDatabase();
        static::assertEquals($data['newTask'], $data['dataModel']);
        static::assertEquals(1, $data['newTask']->getAuthor()->getId());
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
        // Get a user with id "1" as task author thanks to "$newTask" already created
        $data = $this->createTaskInDatabase();
        // Get a user with id "2" to be task last editor
        /** @var UserInterface $authenticatedUser */
        $authenticatedUser = $this->entityManager->getRepository(User::class)->find(2);
        $this->taskDataModelManager->update($data['newTask'], $authenticatedUser);
        $updatedTask = $this->entityManager
            ->getRepository(Task::class)
            ->findOneBy(['title' => $data['newTask']->getTitle()]);
        // Ensure no change was made on author and authenticated user is set as last editor
        static::assertEquals(1, $updatedTask->getAuthor()->getId());
        static::assertEquals(2, $updatedTask->getLastEditor()->getId());
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
        // Get a user with id "1" as task author thanks to "$newTask" already created
        $data = $this->createTaskInDatabase();
        $previousDateOfUpdate = $data['newTask']->getUpdatedAt();
        // Get a user with id "2" to be task last editor
        /** @var UserInterface $authenticatedUser */
        $authenticatedUser = $this->entityManager->getRepository(User::class)->find(2);
        $this->taskDataModelManager->update($data['newTask'], $authenticatedUser);
        $updatedTask = $this->entityManager
            ->getRepository(Task::class)
            ->findOneBy(['title' => $data['newTask']->getTitle()]);
        // Ensure task date of update is "set" (more exactly modified)
        static::assertTrue($previousDateOfUpdate < $updatedTask->getUpdatedAt());
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
        $data = $this->createTaskInDatabase();
        // Inverse two times "isDone" state in order to expect a true toggle feature
        $this->taskDataModelManager->toggle($data['newTask']);
        /** @var Task $toggledTask1 */
        $toggledTask1 = $this->entityManager
            ->getRepository(Task::class)
            ->findOneBy(['title' => $data['newTask']->getTitle()]);
        static::assertTrue($toggledTask1->isDone());
        $this->taskDataModelManager->toggle($toggledTask1);
        $toggledTask2 = $this->entityManager
            ->getRepository(Task::class)
            ->findOneBy(['title' => $data['newTask']->getTitle()]);
        static::assertFalse($toggledTask2->isDone());
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
        $data = $this->createTaskInDatabase();
        $previousDateOfUpdate = $data['newTask']->getUpdatedAt();
        $this->taskDataModelManager->toggle($data['newTask']);
        // Ensure task date of update is set
        static::assertTrue($previousDateOfUpdate < $data['newTask']->getUpdatedAt());
    }

    /**
     * Check that "delete" method will remove Task instance correctly.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testTaskDeletionIsCorrectlyDone(): void
    {
        $taskRepository = $this->entityManager->getRepository(Task::class);
        /** @var ObjectRepository $taskRepository */
        $previousTaskList = $taskRepository->findAll();
        // Remove first task in list
        $this->taskDataModelManager->delete($previousTaskList[0]);
        $nextTaskList = $taskRepository->findAll();
        static::assertSame(\count($previousTaskList) - 1, \count($nextTaskList));
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
        $this->entityManager->close();
        $this->entityManager = null;
        $this->logger = null;
        $this->taskDataModelManager = null;
        parent::tearDown();
    }
}