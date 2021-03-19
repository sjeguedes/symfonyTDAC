<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\Task;
use App\Form\Handler\CreateTaskFormHandler;
use App\Form\Handler\FormHandlerInterface;
use App\Tests\Unit\Form\Handler\Helpers\AbstractTaskFormHandlerTestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class CreateTaskHandlerTest
 *
 * Manage unit tests for task creation form handler.
 */
class CreateTaskHandlerTest extends AbstractTaskFormHandlerTestCase
{
    /**
     * @var MockObject|FormFactoryInterface|null
     */
    private ?FormFactoryInterface $formFactory;

    /**
     * @var MockObject|FlashBagInterface|null
     */
    private ?FlashBagInterface $flashBag;

    /**
     * @var MockObject|EntityManagerInterface|null
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * @var MockObject|DataModelManagerInterface|null
     */
    private ?DataModelManagerInterface $taskDataModelManager;

    /**
     * @var MockObject|TokenStorageInterface|null
     */
    private ?TokenStorageInterface $tokenStorage;

    /**
     * @var FormHandlerInterface|null
     */
    private ?FormHandlerInterface $createTaskHandler;

    /**
     * Create a request instance with expected parameters.
     *
     * @param array  $formData
     * @param string $uri
     * @param string $method
     *
     * @return Request
     */
    private function createRequest(
        array $formData = [],
        string $uri = '/tasks/create',
        string $method = 'POST'
    ): Request {
        // Define default data as valid
        $defaultFormData = [
            'create_task' => [
                'title' => 'Titre de nouvelle tâche',
                'content' => 'Description de nouvelle tâche'
            ]
        ];
        $formData = empty($formData) ? $defaultFormData : $formData;
        $request = Request::create(empty($formData) ? '/tasks/create' : $uri, $method, $formData);

        return $request;
    }

    /**
     * Process a real form made by a form factory instance with capabilities to handle a request.
     *
     * Please note that this method is a helper to refactor code.
     *
     * @param array        $formData
     * @param Request|null $request
     *
     * @return FormInterface
     *
     * @throws \Exception
     */
    private function processForm(array $formData = [], Request $request = null): FormInterface
    {
        $request = $request ?? $this->createRequest($formData);
        // Create a new form handler instance if default request is not used!
        if (!empty($formData) || null !== $request) {
            $this->formFactory = $this->buildFormFactory($request);
            $this->createTaskHandler = new createTaskFormHandler(
                $this->formFactory,
                $this->taskDataModelManager,
                $this->flashBag,
                $this->tokenStorage
            );
        }
        $form = $this->createTaskHandler->process($request, ['dataModel' => new Task()]);

        return $form;
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
        parent::setUp();
        $this->formFactory = $this->buildFormFactory($this->createRequest());
        $this->flashBag = static::createMock(FlashBagInterface::class);
        $this->entityManager = static::createPartialMock(EntityManager::class, ['persist', 'flush']);
        $this->taskDataModelManager = $this->setTaskDataModelManager($this->entityManager);
        $this->tokenStorage = static::createMock(TokenStorageInterface::class);
        $this->createTaskHandler = new createTaskFormHandler(
            $this->formFactory,
            $this->taskDataModelManager,
            $this->flashBag,
            $this->tokenStorage
        );
    }

    /**
     * Provide a set of data to check "execute" method correct return when task creation is not saved.
     *
     * @return \Generator
     */
    public function provideDataToCheckNoExecutionWhenTaskIsNotSaved(): \Generator
    {
        yield [
            'Persist throws an exception for task creation' => [
                'persist' => [
                    'exception' => true,
                    'called'    => true
                ],
                'flush' => [
                    'exception' => false,
                    'called'    => false
                ]
            ]
        ];
        yield [
            'Flush throws an exception for task creation' => [
                'persist' => [
                    'exception' => false,
                    'called'    => true
                ],
                'flush' => [
                    'exception' => true,
                    'called'    => true
                ]
            ]
        ];
    }

    /**
     * Check that "execute" method returns true when submitted form is valid.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsTrueWhenValidFormIsProcessed(): void
    {
        // Get an authenticated user for scenario
        $this->getMockedUserWithExpectations($this->tokenStorage);
        // Process a real submitted form with invalid data thanks to helper method.
        $this->processForm(
            ['create_task' => ['title' => 'Titre de nouvelle tâche', 'content' => 'Description de nouvelle tâche']]
        );
        $isExecuted = $this->createTaskHandler->execute();
        static::assertTrue($isExecuted);
    }

    /**
     * Check that "execute" method returns false when submitted form is invalid.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenInvalidFormIsProcessed(): void
    {
        // Process a real submitted form with invalid data thanks to helper method.
        $this->processForm(
            ['create_task' => ['title' => '', 'content' => '']]
        );
        $isExecuted = $this->createTaskHandler->execute();
        static::assertFalse($isExecuted);
    }

    /**
     * Check that "execute" method returns true when task creation succeeded.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteMethodReturnsTrueWhenTaskCreationPersistenceIsOk(): void
    {
        // Get an authenticated user for scenario
        $this->getMockedUserWithExpectations($this->tokenStorage);
        // Process a real submitted form first with default valid data thanks to helper method.
        $this->processForm();
        $isTaskCreationPersisted = $this->createTaskHandler->execute();
        static::assertTrue($isTaskCreationPersisted);
    }

    /**
     * Check that "execute" method returns false when task creation failed.
     *
     * @dataProvider provideDataToCheckNoExecutionWhenTaskIsNotSaved
     *
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenTaskCreationPersistenceIsNotOk(array $data): void
    {
        // Get an authenticated user for scenario
        $this->getMockedUserWithExpectations($this->tokenStorage);
        // Process a real submitted form first with default valid data thanks to helper method.
        $this->processForm();
        // Throw an exception to be more realistic (when database persistence fails)
        // in order to make the test behave as expected
        $this->entityManager
            ->expects($data['persist']['called'] ? $this->once() : $this->any())
            ->method('persist')
            ->willReturnCallback(function () use ($data) {
                // Make "persist" throw an exception
                if ($data['persist']['exception']) throw new \Exception();
            });
        $this->entityManager
            ->expects($data['flush']['called'] ? $this->once() : $this->any())
            ->method('flush')
            ->willReturnCallback(function () use ($data) {
                // Make "flush" throw an exception
                if ($data['flush']['exception']) throw new \Exception();
            });
        $isTaskCreationPersisted = $this->createTaskHandler->execute();
        static::assertFalse($isTaskCreationPersisted);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->formFactory = null;
        $this->flashBag = null;
        $this->entityManager = null;
        $this->taskDataModelManager = null;
        $this->tokenStorage = null;
        $this->createTaskHandler = null;
        parent::tearDown();
    }
}