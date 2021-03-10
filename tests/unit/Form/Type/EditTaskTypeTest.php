<?php

declare(strict_types=1);

namespace App\Tests\unit\Form\Type;

use App\Entity\Task;
use App\Form\Type\EditTaskType;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\PropertyAccess\Exception\InvalidArgumentException;
use Symfony\Component\Validator\Validation;

/**
 * Class EditTaskTypeTest
 *
 * Manage unit tests for task modification (edit/update) form type.
 *
 * @see Form type unit testing: https://symfony.com/doc/current/form/unit_testing.html
 */
class EditTaskTypeTest extends TypeTestCase
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
                'title'   => 'Tâche modifiée',
                'content' => 'Ceci est une description de tâche modifiée.',
                'isValid' => true
            ]
        ];
        yield [
            'Fails when title data is blank' => [
                'title'   => '',
                'content' => 'Ceci est une description de tâche modifiée.',
                'isValid' => false
            ]
        ];
        yield [
            'Fails when content is blank' => [
                'title'   => 'Tâche modifiée',
                'content' => '',
                'isValid' => false
            ]
        ];
        yield [
            'Fails when title data is not set' => [
                'content' => 'Ceci est une description de tâche modifiée.',
                'isValid' => false
            ]
        ];
        yield [
            'Fails when content is not set' => [
                'title'   => 'Tâche modifiée',
                'isValid' => false
            ]
        ];
    }

    /**
     * Check that data mapping is correctly made when task modification form is submitted.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedModifiedTaskFormMapping(): void
    {
        $dataModel = (new Task())
        ->setTitle('Titre de tâche existante')
        ->setContent('Description de tâche existante');
        $title = 'Titre de tâche modifiée';
        $content = 'Description de tâche modifiée';
        // Clone data model to get the same data automatically set in constructor
        $expectedObject = (clone $dataModel)
            ->setTitle($title)
            ->setContent($content);
        $formData = ['title' => $title, 'content' => $content];
        $form = $this->factory->create(EditTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertEquals($expectedObject, $form->getData());
    }

    /**
     * Check that expected data are validated when task modification form is submitted.
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
    public function testSubmittedModifiedTaskDataValidation(array $data): void
    {
        $dataModel = (new Task())
            ->setTitle('Titre de tâche existante')
            ->setContent('Description de tâche existante');
        // Use arrow function combined to array filtering with flag based on key
        $formData = array_filter($data, fn ($key) => 'isValid' !== $key,ARRAY_FILTER_USE_KEY);
        $form = $this->factory->create(EditTaskType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertSame($data['isValid'], $form->isValid());
    }

    /**
     * Check that data transformation is correctly made when task modification form is submitted.
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
        $formData = ['title' => 'Titre de tâche modifiée', 'content' => 'Description de tâche modifiée'];
        $form = $this->factory->create(EditTaskType::class, $dataModel);
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