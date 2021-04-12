<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form\Type;

use App\Entity\Task;
use App\Form\Type\CreateTaskType;
use App\Tests\Integration\Form\Type\Helpers\AbstractFormTypeKernelTestCase;

/**
 * Class CreateTaskTypeTest
 *
 * Manage integration tests for task creation form type.
 */
class CreateTaskTypeTest extends AbstractFormTypeKernelTestCase
{
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
    }

    /**
     * Provide a set of entity data to check field structure validation.
     *
     * @return \Generator
     */
    public function provideDataStructureToValidate(): \Generator
    {
        yield [
            'Succeeds when data are correct' => [
                'title'   => 'Nouvelle tâche',
                'content' => 'Ceci est une description de nouvelle tâche.',
                'isSynchronized' => true,
                'isValid'        => true
            ]
        ];
        yield [
            'Fails when title data is not unique (unique entity constraint)' => [
                'title'   => 'Task 1: et', // Task 1 real title which exists in test database
                'content' => 'Ceci est une description de nouvelle tâche.',
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when title data is blank' => [
                'title'   => '',
                'content' => 'Ceci est une description de nouvelle tâche.',
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when content data is blank' => [
                'title'   => 'Nouvelle tâche',
                'content' => '',
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when title data is not set' => [
                'content' => 'Ceci est une description de nouvelle tâche.',
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when content data is not set' => [
                'title'   => 'Nouvelle tâche',
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
    }

    /**
     * Check that data mapping is correctly made when task creation form is submitted.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedNewTaskFormMapping(): void
    {
        $dataModel = new Task();
        $title = 'Titre de tâche';
        $content = 'Description de tâche';
        // Clone data model to get the same data automatically set in constructor
        $expectedObject = (clone $dataModel)
            ->setTitle($title)
            ->setContent($content);
        $formData = [
            'task' => [
                'title'   => $title,
                'content' => $content
            ]
        ];
        // Create a real form
        $form = $this->createForm(CreateTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertEquals($expectedObject, $form->getData());
    }

    /**
     * Check that expected data are validated when task creation form is submitted.
     *
     * @dataProvider provideDataStructureToValidate
     *
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedNewTaskDataValidation(array $data): void
    {
        $dataModel = new Task();
        $isValid = $data['isValid'];
        // Use arrow function combined to array filtering with flag based on key
        $formData = array_filter(
            $data,
            fn ($key) => 'isSynchronized' !== $key && 'isValid' !== $key,
            ARRAY_FILTER_USE_KEY
        );
        // Create a real form
        $form = $this->createForm(CreateTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit(['task' => $formData]);
        static::assertSame($isValid, $form->isValid());
    }

    /**
     * Check that data transformation is correctly made when task creation form is submitted.
     *
     * @dataProvider provideDataStructureToValidate
     *
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedNewTaskDataTransformation(array $data): void
    {
        $dataModel = new Task();
        $isSynchronized = $data['isSynchronized'];
        $formData = array_filter(
            $data,
            fn ($key) => 'isSynchronized' !== $key && 'isValid' !== $key,
            ARRAY_FILTER_USE_KEY
        );
        // Create a real form
        $form = $this->createForm(CreateTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit(['task' => $formData]);
        $transformationFailure = 0;
        foreach ($form->get('task') as $childForm) {
            if (null !== $childForm->getTransformationFailure()) {
                $transformationFailure++;
                break;
            }
        }
        // Make use of "$form->isSynchronized()" is not adapted and correct!
        static::assertSame($isSynchronized, 0 === $transformationFailure);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }
}