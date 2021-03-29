<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form\Type;

use App\Entity\User;
use App\Form\Type\DeleteUserType;
use App\Tests\Integration\Form\Type\Helpers\AbstractFormTypeKernelTestCase;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class DeleteUserTypeTest
 *
 * Manage integration tests for user deletion form type.
 */
class DeleteUserTypeTest extends AbstractFormTypeKernelTestCase
{
    /**
     * @var User|null
     */
    private ?User $dataModel;

    /**
     * @var UserPasswordEncoderInterface|null
     */
    private ?UserPasswordEncoderInterface $userPasswordEncoder;

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
        // Get user password encoder private service
        $this->userPasswordEncoder = static::$container->get('security.user_password_encoder.generic');
        // Get a user data model
        $this->dataModel = (new User())
            ->setUsername('username')
            ->setEmail('username@test.fr')
            ->setRoles(['ROLE_USER', 'ROLE_ADMIN']);
        $this->dataModel
            ->setPassword($this->userPasswordEncoder->encodePassword($this->dataModel, 'pass_1'));
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
                'isValid'    => false
            ]
        ];
    }

    /**
     * Check that data mapping is correctly made when user deletion form is submitted.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedDeletedUserFormMapping(): void
    {
        $dataModel = $this->dataModel;
        // Clone data model to get the same data automatically set in constructor
        $expectedObject = clone $dataModel;
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        $formData = [];
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
     *
     * @throws \Exception
     */
    public function testSubmittedDeletedUserDataValidation(array $data): void
    {
        $dataModel = $this->dataModel;
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        $formData = array_filter($data, fn ($key) => 'isValid' !== $key,ARRAY_FILTER_USE_KEY);
        $form = $this->createForm(DeleteUserType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertSame($data['isValid'], $form->isValid());
    }

    /**
     * Check that data transformation is correctly made when user deletion form is submitted.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedDeletedUserDataTransformation(): void
    {
        $dataModel = $this->dataModel;
        // IMPORTANT: there are no valuable tests to proceed at this time, due to no existing field(s)!
        $formData = [];
        $form = $this->createForm(DeleteUserType::class, $dataModel);
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
        $this->dataModel = null;
        $this->userPasswordEncoder = null;
        parent::tearDown();
    }
}