<?php

declare(strict_types=1);

namespace App\Tests\unit\Form\Type;

use App\Entity\Task;
use App\Form\Type\ToggleTaskType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

/**
 * Class ToggleTaskTypeTest
 *
 * Manage unit tests for task toggle form type.
 *
 * @see Form type unit testing: https://symfony.com/doc/current/form/unit_testing.html
 */
class ToggleTaskTypeTest extends TypeTestCase
{
    /**
     * Get form extensions to use it as expected.
     *
     * @return array
     */
    protected function getExtensions(): array
    {
        // Get validator configured to check annotation constraints
        $validator = Validation::createValidatorBuilder()
            ->enableAnnotationMapping()
            ->getValidator();

        return [
            new ValidatorExtension($validator)
        ];
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
     * Check that data mapping is correctly made when task toggle form is submitted.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedToggledTaskFormMapping(): void
    {
        $dataModel = (new Task())
            ->setTitle('Titre de tâche existante')
            ->setContent('Description de tâche existante');
        // Clone data model to get the same data automatically set in constructor
        $expectedObject = clone $dataModel;
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        $formData = [];
        $form = $this->factory->create(ToggleTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertEquals($expectedObject, $form->getData());
    }

    /**
     * Check that expected data are validated when task toggle form is submitted.
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
    public function testSubmittedToggledTaskDataValidation(array $data): void
    {
        $dataModel = (new Task())
            ->setTitle('Titre de tâche existante')
            ->setContent('Description de tâche existante');
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        $formData = array_filter($data, fn ($key) => 'isValid' !== $key,ARRAY_FILTER_USE_KEY);
        $form = $this->factory->create(ToggleTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertSame($data['isValid'], $form->isValid());
    }

    /**
     * Check that data transformation is correctly made when task toggle form is submitted.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedNewTaskDataTransformation(): void
    {
        $dataModel = (new Task())
            ->setTitle('Titre de tâche existante')
            ->setContent('Description de tâche existante');
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        $formData = [];
        $form = $this->factory->create(ToggleTaskType::class, $dataModel);
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