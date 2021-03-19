<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Manager;

use App\Entity\Task;
use App\Form\Type\CreateTaskType;
use App\Form\Type\DeleteTaskType;
use App\Form\Type\EditTaskType;
use App\Form\Type\ToggleTaskType;
use App\Tests\Unit\Helpers\CustomAssertionsTestCaseTrait;
use App\View\Builder\TaskViewModelBuilder;
use App\View\Builder\ViewModelBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;

/**
 * Class TaskViewModelBuilderTest
 *
 * Manage unit tests for task actions view model builder.
 */
class TaskViewModelBuilderTest extends TestCase
{
    use CustomAssertionsTestCaseTrait;

    /**
     * @var MockObject|EntityManagerInterface|null
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * @var MockObject|FormFactoryInterface|null
     */
    protected ?FormFactoryInterface $formFactory;

    /**
     * @var ViewModelBuilderInterface|null
     */
    private ?ViewModelBuilderInterface $viewModelBuilder;

    /**
     * @var \StdClass|null
     */
    private ?\StdClass $viewModel;

    /**
     * Get a test task collection.
     *
     * @return array|Task[]
     */
    private function getTaskCollection(): array
    {
        $task1 = static::createPartialMock(Task::class, ['getId']);
        $task2 = static::createPartialMock(Task::class, ['getId']);
        $task3 = static::createPartialMock(Task::class, ['getId']);
        $task1
            ->method('getId')
            ->willReturn(1);
        $task2
            ->method('getId')
            ->willReturn(2);
        $task3
            ->method('getId')
            ->willReturn(3);

        return [$task1, $task2, $task3];
    }

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
        $this->formFactory = Forms::createFormFactory();
        $this->viewModel = null;
        $this->viewModelBuilder = new TaskViewModelBuilder(
            $this->entityManager,
            $this->formFactory
        );
    }

    /**
     * Provide a set of view references to check "form" parameter instance type passed to merged data.
     *
     * @return array
     */
    public function provideViewReferenceToCheckFormInstanceTypeInMergedData(): array
    {
        return [
            'Uses "task toggle" view'   => ['toggle_task'],
            'Uses "task deletion" view' => ['delete_task']
        ];
    }

    /**
     * Check that task view model builder cannot create an instance using a view reference.
     *
     * @return void
     */
    public function testTaskViewModelCannotCreateInstanceUsingViewReference(): void
    {
        static::expectException(\RuntimeException::class);
        $this->viewModelBuilder->create('unexpected_view_reference');
    }

    /**
     * Check that task view model builder cannot create an instance using wrong "form" merged data instance.
     *
     * Please note that this "form" data is used in "toggle" and "deletion" views.
     *
     * @dataProvider provideViewReferenceToCheckFormInstanceTypeInMergedData
     *
     * @param string $viewReference
     *
     * @return void
     */
    public function testTaskViewModelCannotCreateInstanceUsingWrongFormMergedData(string $viewReference): void
    {
        $entityRepository = static::createPartialMock(EntityRepository::class, ['findAll']);
        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepository);
        // IMPORTANT: maybe use a custom query method with scalar result instead of "findAll" later!
        // An empty array is sufficient: a Task collection is unneeded due to tested exception!
        $entityRepository
            ->expects($this->any())
            ->method('findAll')
            ->willReturn([]);
        $testObject = new \stdClass();
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage(
            sprintf('"form" merged data must implement %s',FormInterface::class)
        );
        $this->viewModelBuilder->create($viewReference, ['form' => $testObject]);
    }

    /**
     * Check that each "toggle task" form should be suffixed with an integer index.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testToggleTaskFormNameShouldBeSuffixedWithAIntegerIndex(): void
    {
        $entityRepository = static::createPartialMock(EntityRepository::class, ['findAll']);
        $task = $this->getTaskCollection()[0];
        // Create a real toggle form with a wrong name (without index as suffix)
        // to ease test with "task 1" as data model
        $currentForm = $this->formFactory->createNamed('toggle_task', ToggleTaskType::class, $task);
        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($entityRepository);
        // IMPORTANT: maybe use a custom query method with scalar result instead of "findAll" later!
        // An empty array is sufficient: a Task collection is unneeded due to tested exception!
        $entityRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn([]);
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage('Current form name suffix is expected to be an integer as index!');
        $this->viewModelBuilder->create('toggle_task', ['form' => $currentForm]);
    }

    /**
     * Check that "task creation" view model is correctly built.
     *
     * @return void
     */
    public function testTaskCreationActionTaskViewModelBuildIsOk(): void
    {
        $task = $this->getTaskCollection()[0];
        // Create a real form to ease test with "task 1" as data model
        $currentForm = $this->formFactory->createNamed('create_task', CreateTaskType::class, $task);
        $viewModel = $this->viewModelBuilder->create('create_task', ['form' => $currentForm]);
        static::assertObjectNotHasAttribute('form', $viewModel);
        static::assertObjectHasAttribute('createTaskFormView', $viewModel);
        static::assertInstanceOf(FormView::class, $viewModel->createTaskFormView);
    }

    /**
     * Check that "task update" view model is correctly built.
     *
     * @return void
     */
    public function testTaskUpdateActionTaskViewModelBuildIsOk(): void
    {
        $testTaskList = $this->getTaskCollection();
        $task = $testTaskList[0];
        // Create a real form to ease test with "task 1" as data model
        $currentForm = $this->formFactory->createNamed('edit_task', EditTaskType::class, $task);
        $viewModel = $this->viewModelBuilder->create('edit_task', ['form' => $currentForm, 'task' => $task]);
        static::assertObjectNotHasAttribute('form', $viewModel);
        static::assertObjectHasAttribute('editTaskFormView', $viewModel);
        static::assertInstanceOf(FormView::class, $viewModel->editTaskFormView);
        static::assertObjectHasAttribute('taskId', $viewModel);
        static::assertSame(1, $viewModel->taskId);
    }

    /**
     * Check that "task toggle" view model is correctly built.
     *
     * @return void
     */
    public function testTaskToggleActionTaskViewModelBuildIsOk(): void
    {
        $entityRepository = static::createPartialMock(EntityRepository::class, ['findAll']);
        $testTaskList = $this->getTaskCollection();
        $task = $testTaskList[1];
        // Create a real form to ease test with "task 2" as data model
        $currentForm = $this->formFactory->createNamed('toggle_task_2', ToggleTaskType::class, $task);
        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($entityRepository);
        // IMPORTANT: maybe use a custom query method with scalar result instead of "findAll" later!
        $entityRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($testTaskList);
        $viewModel = $this->viewModelBuilder->create('toggle_task', ['form' => $currentForm]);
        static::assertObjectNotHasAttribute('form', $viewModel);
        static::assertObjectHasAttribute('tasks', $viewModel);
        static::assertCount(3, $viewModel->tasks);
        static::assertContainsOnlyInstancesOf(Task::class, $viewModel->tasks);
        static::assertObjectHasAttribute('toggleTaskFormViews', $viewModel);
        static::assertCount(3, $viewModel->toggleTaskFormViews);
        static::assertContainsOnlyInstancesOf(FormView::class, $viewModel->toggleTaskFormViews);
    }

    /**
     * Check that "task deletion" view model is correctly built.
     *
     * @return void
     */
    public function testTaskDeletionActionTaskViewModelBuildIsOk(): void
    {
        $entityRepository = static::createPartialMock(EntityRepository::class, ['findAll']);
        $testTaskList = $this->getTaskCollection();
        $task = $testTaskList[1];
        // Create a real form to ease test with "task 2" as data model
        $currentForm = $this->formFactory->createNamed('delete_task_2', DeleteTaskType::class, $task);
        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($entityRepository);
        // IMPORTANT: maybe use a custom query method with scalar result instead of "findAll" later!
        $entityRepository
            ->expects($this->once())
            ->method('findAll')
            ->willReturn($testTaskList);
        $viewModel = $this->viewModelBuilder->create('delete_task', ['form' => $currentForm]);
        static::assertObjectNotHasAttribute('form', $viewModel);
        static::assertObjectHasAttribute('tasks', $viewModel);
        static::assertCount(3, $viewModel->tasks);
        static::assertContainsOnlyInstancesOf(Task::class, $viewModel->tasks);
        static::assertObjectHasAttribute('deleteTaskFormViews', $viewModel);
        static::assertCount(3, $viewModel->deleteTaskFormViews);
        static::assertContainsOnlyInstancesOf(FormView::class, $viewModel->deleteTaskFormViews);
    }

    /**
     * Check that view model build fails if at least one merged data key is not of string type.
     *
     * @return void
     */
    public function testTaskViewModelCreationIsNotOkWhenMergedDataKeyIsNotOfStringType(): void
    {
        static::expectException(\InvalidArgumentException::class);
        $this->viewModelBuilder->create(null, ['string' => 'value1', 0 => 'value2']);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->entityManager = null;
        $this->formFactory = null;
        $this->viewModel = null;
        $this->viewModelBuilder = null;
    }
}