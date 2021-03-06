<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form\Type;

use App\Entity\Task;
use App\Form\Type\EditTaskType;
use App\Tests\Integration\Form\Type\Helpers\AbstractFormTypeKernelTestCase;

/**
 * Class EditTaskTypeTest
 *
 * Manage integration tests for task modification (edit/update) form type.
 */
class EditTaskTypeTest extends AbstractFormTypeKernelTestCase
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
                'title'   => 'Tâche modifiée',
                'content' => 'Ceci est une description de tâche modifiée.',
                'isSynchronized' => true,
                'isValid'        => true
            ]
        ];
        yield [
            'Fails when title data is not unique (unique entity constraint)' => [
                'title'   => 'Task 2: voluptatem', // Task 2 real title which exists in test database
                'content' => 'Ceci est une description de tâche modifiée.',
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when title data is blank' => [
                'title'   => '',
                'content' => 'Ceci est une description de tâche modifiée.',
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when content is blank' => [
                'title'   => 'Tâche modifiée',
                'content' => '',
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when title data is not set' => [
                'content' => 'Ceci est une description de tâche modifiée.',
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when content is not set' => [
                'title'   => 'Tâche modifiée',
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
    }

    /**
     * Check that data mapping is correctly made when task modification form is submitted.
     *
     * @return void
     */
    public function testSubmittedModifiedTaskFormMapping(): void
    {
        // Get existing task with id "1"
        $dataModel = $this->entityManager->getRepository(Task::class)->find(1);
        $title = 'Titre de tâche modifiée';
        $content = 'Description de tâche modifiée';
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
        $form = $this->createForm(EditTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertEquals($expectedObject, $form->getData());
    }

    /**
     * Check that expected data are validated when task modification form is submitted.
     *
     * @dataProvider provideDataStructureToValidate
     *
     * @param array $data
     *
     * @return void
     */
    public function testSubmittedModifiedTaskDataValidation(array $data): void
    {
        // Get existing task with id "1"
        $dataModel = $this->entityManager->getRepository(Task::class)->find(1);
        $isValid = $data['isValid'];
        // Use arrow function combined to array filtering with flag based on key
        $formData = array_filter(
            $data,
            fn ($key) => 'isSynchronized' !== $key && 'isValid' !== $key,
            ARRAY_FILTER_USE_KEY
        );
        // Create a real form
        $form = $this->createForm(EditTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit(['task' => $formData]);
        static::assertSame($isValid, $form->isValid());
    }

    /**
     * Check that data transformation is correctly made when task modification form is submitted.
     *
     * @dataProvider provideDataStructureToValidate
     *
     * @param array $data
     *
     * @return void
     */
    public function testSubmittedModifiedTaskDataTransformation(array $data): void
    {
        // Get existing task with id "1"
        $dataModel = $this->entityManager->getRepository(Task::class)->find(1);
        $isSynchronized = $data['isSynchronized'];
        // Use arrow function combined to array filtering with flag based on key
        $formData = array_filter(
            $data,
            fn ($key) => 'isSynchronized' !== $key && 'isValid' !== $key,
            ARRAY_FILTER_USE_KEY
        );
        // Create a real form
        $form = $this->createForm(EditTaskType::class, $dataModel);
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