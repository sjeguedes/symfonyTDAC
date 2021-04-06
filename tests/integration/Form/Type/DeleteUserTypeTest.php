<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form\Type;

use App\Entity\User;
use App\Form\Type\DeleteUserType;
use App\Tests\Integration\Form\Type\Helpers\AbstractFormTypeKernelTestCase;

/**
 * Class DeleteUserTypeTest
 *
 * Manage integration tests for user deletion form type.
 */
class DeleteUserTypeTest extends AbstractFormTypeKernelTestCase
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
                'isSynchronized' => true,
                'isValid'        => true
            ]
        ];
        yield [
            'Fails when unexpected data are set' => [
                // No data is expected to be submitted at this time!
                'unexpected' => 'Test',
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
    }

    /**
     * Check that data mapping is correctly made when user deletion form is submitted.
     *
     * @return void
     */
    public function testSubmittedDeletedUserFormMapping(): void
    {
        // Get existing user with id "1"
        $dataModel = $this->entityManager->getRepository(User::class)->find(1);
        // Clone data model to get the same data automatically set in constructor
        $expectedObject = clone $dataModel;
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        $formData = [];
        // Create a real form
        $form = $this->createForm(DeleteUserType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertEquals($expectedObject, $form->getData());
    }

    /**
     * Check that expected data are validated when user deletion form is submitted.
     *
     * @dataProvider provideDataStructureToValidate
     *
     * @param array $data
     *
     * @return void
     */
    public function testSubmittedDeletedUserDataValidation(array $data): void
    {
        // Get existing user with id "1"
        $dataModel = $this->entityManager->getRepository(User::class)->find(1);
        $isValid = $data['isValid'];
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        // Use arrow function combined to array filtering with flag based on key
        $formData = array_filter(
            $data,
            fn ($key) => 'isSynchronized' !== $key && 'isValid' !== $key,
            ARRAY_FILTER_USE_KEY
        );
        // Create a real form
        $form = $this->createForm(DeleteUserType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertSame($isValid, $form->isValid());
    }

    /**
     * Check that data transformation is correctly made when user deletion form is submitted.
     *
     * @dataProvider provideDataStructureToValidate
     *
     * @param array $data
     *
     * @return void
     */
    public function testSubmittedDeletedUserDataTransformation(array $data): void
    {
        // Get existing user with id "1"
        $dataModel = $this->entityManager->getRepository(User::class)->find(1);
        $isSynchronized = $data['isSynchronized'];
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        // Use arrow function combined to array filtering with flag based on key
        $formData = array_filter(
            $data,
            fn ($key) => 'isSynchronized' !== $key && 'isValid' !== $key,
            ARRAY_FILTER_USE_KEY
        );
        // Create a real form
        $form = $this->createForm(DeleteUserType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        // CAUTION: make use of "$form->isSynchronized()" is not always adapted and correct with nested form(s)!
        static::assertSame($isSynchronized, $form->isSynchronized());
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