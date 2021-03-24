<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\Task;
use App\Form\Handler\EditTaskFormHandler;
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
 * Class EditTaskFormHandlerTest
 *
 * Manage unit tests for task update form handler.
 */
class EditTaskFormHandlerTest extends AbstractTaskFormHandlerTestCase
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
    private ?FormHandlerInterface $editTaskHandler;

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
        string $uri = '/tasks/1/edit',
        string $method = 'POST'
    ): Request {
        // Define default data as valid
        $defaultFormData = [
            'edit_task' => [
                'task' => [
                    'title'   => 'Titre de tâche modifiée',
                    'content' => 'Description de tâche modifiée'
                ]
            ]
        ];
        $formData = empty($formData) ? $defaultFormData : $formData;
        $request = Request::create(empty($formData) ? '/tasks/1/edit' : $uri, $method, $formData);

        return $request;
    }

    /**
     * Process a real form made by a form factory instance with capabilities to handle a request.
     *
     * Please note that this method is a helper to refactor code.
     *
     * @param array        $formData a mandatory set of data for update form to compare real change(s)
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
        if (null !== $request) {
            $this->formFactory = $this->buildFormFactory($request);
            $this->editTaskHandler = new EditTaskFormHandler(
                $this->formFactory,
                $this->taskDataModelManager,
                $this->flashBag,
                $this->tokenStorage
            );
        }
        $existingTask = (new Task())
            ->setTitle('Titre de tâche existante')
            ->setContent('Description de tâche existante');
        $form = $this->editTaskHandler->process($request, ['dataModel' => $existingTask]);

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
        $this->entityManager = static::createPartialMock(EntityManager::class, ['flush']);
        $this->taskDataModelManager = $this->setTaskDataModelManager($this->entityManager);
        $this->tokenStorage = static::createMock(TokenStorageInterface::class);
        $this->editTaskHandler = new EditTaskFormHandler(
            $this->formFactory,
            $this->taskDataModelManager,
            $this->flashBag,
            $this->tokenStorage
        );
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
        $this->processForm();
        $isExecuted = $this->editTaskHandler->execute();
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
            [
                'edit_task' => [
                    'task' => [
                        'title'   => '',
                        'content' => ''
                    ]
                ]
            ]
        );
        $isExecuted = $this->editTaskHandler->execute();
        static::assertFalse($isExecuted);
    }

    /**
     * Check that "execute" method returns true when task update succeeded.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteMethodReturnsTrueWhenTaskUpdateFlushIsOk(): void
    {
        // Get an authenticated user for scenario
        $this->getMockedUserWithExpectations($this->tokenStorage);
        // Process a real submitted form first with default valid data thanks to helper method.
        $this->processForm(
            [
                'edit_task' => [
                    'task' => [
                        'title'   => 'Titre de tâche modifiée',
                        'content' => 'Description de tâche modifiée'
                    ]
                ]
            ]
        );
        $isTaskUpdateFlushed = $this->editTaskHandler->execute();
        static::assertTrue($isTaskUpdateFlushed);
    }

    /**
     * Check that "execute" method returns false when task update failed.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenTaskUpdateFlushIsNotOk(): void
    {
        // Get an authenticated user for scenario
        $this->getMockedUserWithExpectations($this->tokenStorage);
        // Process a real submitted form first with default valid data thanks to helper method.
        $this->processForm();
        // Throw an exception to be more realistic (when database persistence fails)
        // in order to make the test behave as expected
        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception());
        $isTaskUpdateFlushed = $this->editTaskHandler->execute();
        static::assertFalse($isTaskUpdateFlushed);
    }

    /**
     * Check that "execute" method returns false when task update is submitted with no change.
     *
     * Please note that it is a kind of feature which aims at improving user experience.
     * "No change" means form in data are equals to initial data.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenTaskUpdateMakesNoChange(): void
    {
        // Process a real submitted form with invalid data thanks to helper method.
        // No data change is submitted
        $this->processForm(
            [
                'edit_task' => [
                    'task' => [
                        'title'   => 'Titre de tâche existante',
                        'content' => 'Description de tâche existante'
                    ]
                ]
            ]
        );
        $request = $this->createRequest();
        $isTaskUpdateFlushed = $this->editTaskHandler->execute();
        static::assertFalse($isTaskUpdateFlushed);
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
        $this->editTaskHandler = null;
        parent::tearDown();
    }
}