<?php

declare(strict_types=1);

namespace App\Tests\unit\Form\Handler;

use App\Entity\Task;
use App\Form\Handler\AbstractFormHandler;
use App\Form\Handler\FormHandlerInterface;
use App\Form\Type\CreateTaskType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\HttpFoundation\HttpFoundationExtension;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

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
     * Assert that an instance implements a particular interface.
     *
     * Please note this is a custom assertion.
     *
     * @param string $expectedInterface
     * @param object $testObject
     * @param string $message
     *
     * @return void
     */
    private static function assertImplements(string $expectedInterface, object $testObject, string $message = ''): void
    {
        // If "false" is returned, an error happened with this native function.
        $interfacesNames = class_implements($testObject);
        $value = false !== $interfacesNames ? \in_array($expectedInterface, $interfacesNames) : false;
        self::assertThat($value, self::isTrue(), $message);
    }

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
        // "TypeTest" is a fake form type name by default.
        $this->formHandler = new class (
            $this->formFactory,
            'TypeTest',
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
            'Uses task creation form type' => [CreateTaskType::class]
            // IMPORTANT: complete other existing types here later!
        ];
    }

    /**
     * Check that a form handler cannot process without "dataModel" data key.
     *
     * @return void
     */
    public function testFormCannotBeProcessedWithoutDataModelKey(): void
    {
        static::expectException(\OutOfBoundsException::class);
        $this->formHandler->process(new Request());
    }

    /**
     * Check that "process" method returns a correct expected implementation of form.
     *
     * @dataProvider provideFormTypeNames
     *
     * @param string $formTypeName
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testProcessReturnsAFormInstanceWithCorrectImplementation(string $formTypeName): void
    {
        // Use a concrete form factory with Http foundation extension to be able to handle a Request.
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new HttpFoundationExtension())
            ->getFormFactory();
        // Use anonymous class
        $this->formHandler = new class (
            $this->formFactory,
            $formTypeName,
            $this->flashBag
        ) extends AbstractFormHandler {};
        $form = $this->formHandler->process(new Request(), ['dataModel' => new Task()]);
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
    public function testProcessSetsCorrectSuccessStateDependingOnFormSubmissionAndValidation(array $data): void
    {
        // Use an abstract class mock instead of anonymous class for this simple case
        $this->formHandler = static::getMockForAbstractClass(
            AbstractFormHandler::class,
            [$this->formFactory, 'TypeTest', $this->flashBag],
            '',
            true
        );
        $form = static::createMock(FormInterface::class);
        $this->formFactory
            ->expects($this->once())
            ->method('create')
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