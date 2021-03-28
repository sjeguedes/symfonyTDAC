<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\Task;
use App\Form\Handler\FormHandlerInterface;
use App\Form\Handler\ToggleTaskFormHandler;
use App\Tests\Unit\Form\Handler\Helpers\AbstractTaskFormHandlerTestCase;
use App\Tests\Unit\Helpers\EntityReflectionTestCaseTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Class ToggleTaskFormHandlerTest
 *
 * Manage unit tests for task toggle form handler.
 */
class ToggleTaskFormHandlerTest extends AbstractTaskFormHandlerTestCase
{
    use EntityReflectionTestCaseTrait;

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
     * @var FormHandlerInterface|null
     */
    private ?FormHandlerInterface $toggleTaskHandler;

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
        string $uri = '/tasks/1/toggle',
        string $method = 'PATCH'
    ): Request {
        // Define default data as valid
        $defaultFormData = [
            'toggle_task_1' => [
                // No fields are set at this time!
            ]
        ];
        $formData = empty($formData) ? $defaultFormData : $formData;
        $request = Request::create(empty($formData) ? '/tasks/1/toggle' : $uri, $method, $formData);

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
        if (null !== $request) {
            $this->formFactory = $this->buildFormFactory($request, Task::class);
            $this->toggleTaskHandler = new ToggleTaskFormHandler(
                $this->formFactory,
                $this->taskDataModelManager,
                $this->flashBag
            );
        }
        // Use reflection to get a fake existing task with id
        $existingTask = (new Task())
            ->setTitle('Titre de tâche existante')
            ->setContent('Contenu de tâche existante');
        $existingTask = $this->setEntityIdByReflection($existingTask, 1);
        $form = $this->toggleTaskHandler->process($request, ['dataModel' => $existingTask]);
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
        $this->formFactory = $this->buildFormFactory($this->createRequest(), Task::class);
        $this->flashBag = static::createMock(FlashBagInterface::class);
        $this->entityManager = static::createPartialMock(EntityManager::class, ['flush']);
        $this->taskDataModelManager = $this->setTaskDataModelManager($this->entityManager);
        $this->toggleTaskHandler = new ToggleTaskFormHandler(
            $this->formFactory,
            $this->taskDataModelManager,
            $this->flashBag
        );
    }

    /**
     * Check that "toggle" method returns false when submitted form is invalid.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenInvalidFormIsProcessed(): void
    {
        // Process a real submitted form with invalid data thanks to helper method.
        // No field is set, and no data is expected at this time!
        $this->processForm(
            ['toggle_task_1' => ['unexpected' => 'Test']] // use real field(s) later if needed
        );
        $isExecuted = $this->toggleTaskHandler->execute();
        static::assertFalse($isExecuted);
    }

    /**
     * Check that "execute" method returns true when task update succeeded.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteMethodReturnsTrueWhenTaskToggleFlushIsOk(): void
    {
        // Process a real submitted form first with default valid data thanks to helper method.
        $this->processForm();
        $isTaskToggleFlushed = $this->toggleTaskHandler->execute();
        static::assertTrue($isTaskToggleFlushed);
    }

    /**
     * Check that "execute" method returns false when task update failed.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenTaskTaskToggleFlushIsNotOk(): void
    {
        // Process a real submitted form first with default valid data thanks to helper method.
        $this->processForm();
        // Throw an exception to be more realistic (when database persistence fails)
        // in order to make the test behave as expected
        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception());
        $isTaskToggleFlushed = $this->toggleTaskHandler->execute();
        static::assertFalse($isTaskToggleFlushed);
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
        $this->toggleTaskHandler = null;
        parent::tearDown();
    }
}