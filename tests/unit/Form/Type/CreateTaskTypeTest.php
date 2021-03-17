<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Type;

use App\Entity\Task;
use App\Form\Type\CreateTaskType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\Validator\Validation;

/**
 * Class CreateTaskTypeTest
 *
 * Manage unit tests for task creation form type.
 *
 * @see Form type unit testing: https://symfony.com/doc/current/form/unit_testing.html
 */
class CreateTaskTypeTest extends TypeTestCase
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
            'Succeeds when data are correct' => [
                'title'   => 'Nouvelle tâche',
                'content' => 'Ceci est une description de nouvelle tâche.',
                'isValid' => true
            ]
        ];
        yield [
            'Fails when title data is blank' => [
                'title'   => '',
                'content' => 'Ceci est une description de nouvelle tâche.',
                'isValid' => false
            ]
        ];
        yield [
            'Fails when content is blank' => [
                'title'   => 'Nouvelle tâche',
                'content' => '',
                'isValid' => false
            ]
        ];
        yield [
            'Fails when title data is not set' => [
                'content' => 'Ceci est une description de nouvelle tâche.',
                'isValid' => false
            ]
        ];
        yield [
            'Fails when content is not set' => [
                'title'   => 'Nouvelle tâche',
                'isValid' => false
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
        $formData = ['title' => $title, 'content' => $content];
        $form = $this->factory->create(CreateTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertEquals($expectedObject, $form->getData());
    }

    /**
     * Check that expected data are validated when task creation form is submitted.
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
    public function testSubmittedNewTaskDataValidation(array $data): void
    {
        $dataModel = new Task();
        $isValid = $data['isValid'];
        // Use arrow function combined to array filtering with flag based on key
        $formData = array_filter($data, fn ($key) => 'isValid' !== $key, ARRAY_FILTER_USE_KEY);
        $form = $this->factory->create(CreateTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertSame($isValid, $form->isValid());
    }

    /**
     * Check that data transformation is correctly made when task creation form is submitted.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedNewTaskDataTransformation(): void
    {
        $dataModel = new Task();
        $formData = ['title' => 'Titre de tâche', 'content' => 'Description de tâche'];
        $form = $this->factory->create(CreateTaskType::class, $dataModel);
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