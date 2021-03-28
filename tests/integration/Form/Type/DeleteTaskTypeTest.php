<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form\Type;

use App\Entity\Task;
use App\Form\Type\DeleteTaskType;
use App\Tests\Integration\Form\Type\Helpers\AbstractFormTypeKernelTestCase;

/**
 * Class DeleteTaskTypeTest
 *
 * Manage integration tests for task deletion form type.
 */
class DeleteTaskTypeTest extends AbstractFormTypeKernelTestCase
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
            'Succeeds when no data exists' => [
                // No field exists at this time!
                'isValid' => true
            ]
        ];
        yield [
            'Fails when unexpected data are set' => [
                // No data is expected to be submitted at this time!
                'unexpected' => 'Test',
                'isValid' => false
            ]
        ];
    }

    /**
     * Check that data mapping is correctly made when task deletion form is submitted.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedDeletedTaskFormMapping(): void
    {
        $dataModel = (new Task())
            ->setTitle('Titre de tâche existante')
            ->setContent('Description de tâche existante');
        // Clone data model to get the same data automatically set in constructor
        $expectedObject = clone $dataModel;
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        $formData = [];
        $form = $this->createForm(DeleteTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertEquals($expectedObject, $form->getData());
    }

    /**
     * Check that expected data are validated when task deletion form is submitted.
     *
     * Please note that each validation constraint is already checked
     * in TaskTest::testValidationRulesAreCorrectlySet().
     *
     * @dataProvider provideDataStructureToValidate
     *
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedDeletedTaskDataValidation(array $data): void
    {
        $dataModel = (new Task())
            ->setTitle('Titre de tâche existante')
            ->setContent('Description de tâche existante');
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        $formData = array_filter($data, fn ($key) => 'isValid' !== $key,ARRAY_FILTER_USE_KEY);
        $form = $this->createForm(DeleteTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertSame($data['isValid'], $form->isValid());
    }

    /**
     * Check that data transformation is correctly made when task deletion form is submitted.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedDeletedTaskDataTransformation(): void
    {
        $dataModel = (new Task())
            ->setTitle('Titre de tâche existante')
            ->setContent('Description de tâche existante');
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        $formData = [];
        $form = $this->createForm(DeleteTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertTrue($form->isSynchronized());
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