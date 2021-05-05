<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Handler;

use App\Entity\Task;
use App\Entity\User;
use App\Form\Handler\AbstractFormHandler;
use App\Form\Handler\FormHandlerInterface;
use App\Form\Transformer\ArrayToExplodedStringModelTransformer;
use App\Form\Type\Base\BaseUserType;
use App\Form\Type\CreateTaskType;
use App\Form\Type\DeleteTaskType;
use App\Form\Type\DeleteUserType;
use App\Form\Type\EditTaskType;
use App\Form\Type\EditUserType;
use App\Form\Type\ToggleTaskType;
use App\Tests\Unit\Helpers\CustomAssertionsTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Validator\Validation;

/**
 * Class AbstractFormHandlerTest
 *
 * Manage unit tests for form handlers common logic declared in AbstractFormHandler class.
 *
 * @see Abstract class mock: https://phpunit.readthedocs.io/en/stable/test-doubles.html#mocking-traits-and-abstract-classes
 * @see Anonymous class: https://www.php.net/manual/en/language.oop5.anonymous.php
 */
class AbstractFormHandlerTest extends TestCase
{
    use CustomAssertionsTestCaseTrait;

    /**
     * @var MockObject|FormFactoryInterface|null
     */
    private ?FormFactoryInterface $formFactory;

    /**
     * @var FormHandlerInterface|null
     */
    private ?FormHandlerInterface $formHandler;

    /**
     * @var MockObject|FlashBagInterface
     */
    private ?FlashBagInterface $flashBag;

    /**
     * Setup needed instance(s).
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->formFactory = static::createMock(FormFactoryInterface::class);
        $this->flashBag = static::createMock(FlashBagInterface::class);
        // Use an anonymous class to represent a concrete form handler
        // "fake_form_name" and "FakeFormType" are fake form name and type name by default.
        $this->formHandler = new class (
            $this->formFactory,
            'fake_form_name',
            'FakeFormType',
            $this->flashBag
        ) extends AbstractFormHandler {};
    }

    /**
     * Provide a set of behaviors with corresponding result to check success state.
     *
     * @return \Generator
     */
    public function provideDataToCheckSuccessStateResult(): \Generator
    {
        yield [
            'Success state is false when form is not submitted' => [
                'isSubmitted' => false,
                'isValid'     => true,
                'isSuccess'   => false
            ]
        ];
        yield [
            'Success state is false when form is not valid' => [
                'isSubmitted' => true,
                'isValid'     => false,
                'isSuccess'   => false
            ]
        ];
        yield [
            'Success state is true when form is submitted and valid' => [
                'isSubmitted' => true,
                'isValid'     => true,
                'isSuccess'   => true
            ]
        ];
    }

    /**
     * Provide a set of Symfony form types class names used in application.
     *
     * @return array
     */
    public function provideFormTypeNames(): array
    {
        return [
            'Uses task creation form type'       => ['create_task', CreateTaskType::class, Task::class],
            'Uses task update form type'         => ['edit_task', EditTaskType::class, Task::class],
            'Uses task toggle first form type'   => ['toggle_task_1', ToggleTaskType::class, Task::class],
            'Uses task deletion first form type' => ['delete_task_1', DeleteTaskType::class, Task::class],
            'Uses user update form type'         => ['edit_user', EditUserType::class, User::class],
            'Uses user deletion first form type' => ['delete_user_1', DeleteUserType::class, User::class]
        ];
    }

    /**
     * Check that a form handler cannot process without "dataModel" data key.
     *
     * @return void
     */
    public function testFormCannotBeProcessedWithoutDataModelKey(): void
    {
        static::expectException(\RuntimeException::class);
        $this->formHandler->process(new Request());
    }

    /**
     * Check that "process" method returns a correct expected implementation of form.
     *
     * @dataProvider provideFormTypeNames
     *
     * @param string $formName
     * @param string $formTypeName
     * @param string $modelName
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testProcessReturnsAFormInstanceWithCorrectImplementation(
        string $formName,
        string $formTypeName,
        string $modelName
    ): void {
        // Use a concrete form factory with Http foundation extension to be able to handle a Request.
        $formFactoryBuilder = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension());
        // Add "BaseUserType" pre-loaded extension for user actions
        if (User::class === $modelName) {
            // Get a custom "roles" model transformer instance to make form work
            $dataTransformer = new ArrayToExplodedStringModelTransformer();
            $formFactoryBuilder->addExtension(new PreloadedExtension([new BaseUserType($dataTransformer)], []));
            // Get validator extension to avoid issue with all user forms
            $validator = Validation::createValidatorBuilder()->getValidator();
            $formFactoryBuilder->addExtension(new ValidatorExtension($validator));
        }
        $this->formFactory = $formFactoryBuilder->getFormFactory();
        // Use anonymous class
        $this->formHandler = new class (
            $this->formFactory,
            $formName,
            $formTypeName,
            $this->flashBag
        ) extends AbstractFormHandler {};
        $form = $this->formHandler->process(new Request(), ['dataModel' => new $modelName()]);
        // Use custom assertion above to check implementation
        static::assertImplements(FormInterface::class, $form);
    }

    /**
     * Check that a form handler returns true or false for success state
     * with/without a form submission/validation.
     *
     * Please note that a "true" value is expected if a form is submitted and valid.
     * A valid model is not checked here.
     *
     * @dataProvider provideDataToCheckSuccessStateResult
     *
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testProcessSetsCorrectSuccessStateOnFormSubmissionAndValidation(array $data): void
    {
        // Use an abstract class mock instead of anonymous class for this simple case
        $this->formHandler = static::getMockForAbstractClass(
            AbstractFormHandler::class,
            [$this->formFactory, 'form_name', 'FakeFormType', $this->flashBag],
            '',
            true
        );
        $form = static::createMock(FormInterface::class);
        $this->formFactory
            ->expects($this->once())
            ->method('createNamed')
            ->willReturn($form);
        $form
            ->expects($data['isSubmitted'] ? $this->once() : $this->any())
            ->method('isSubmitted')
            ->willReturn($data['isSubmitted']);
        $form
            ->expects($data['isSubmitted'] && $data['isValid'] ? $this->once() : $this->any())
            ->method('isValid')
            ->willReturn($data['isValid']);
        // Valid model is not checked here: form type unit test should control this case!
        $this->formHandler->process(new Request(), ['dataModel' => new Task()]);
        static::assertSame($data['isSuccess'], $this->formHandler->isSuccess());
    }

    /**
     * Check that success state cannot be obtained without having processed a form.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSuccessStateCannotBeObtainedWhenFormIsNotProcessed(): void
    {
        static::expectException(\RuntimeException::class);
        $this->formHandler->isSuccess();
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
        $this->formHandler = null;
    }
}