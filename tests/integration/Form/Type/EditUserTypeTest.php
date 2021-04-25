<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form\Type;

use App\Entity\User;
use App\Form\Transformer\ArrayToExplodedStringModelTransformer;
use App\Form\Type\Base\BaseUserType;
use App\Form\Type\EditUserType;
use App\Tests\Integration\Form\Type\Helpers\AbstractFormTypeKernelTestCase;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;

/**
 * Class EditUserTypeTest
 *
 * Manage integration tests for user modification (edit/update) form type.
 */
class EditUserTypeTest extends AbstractFormTypeKernelTestCase
{
    /**
     * @var DataTransformerInterface|null
     */
    private ?DataTransformerInterface $dataTransformer;

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
        // Get a custom "roles" model transformer instance
        $this->dataTransformer = new ArrayToExplodedStringModelTransformer();
        // Get  a real form factory (by overriding the one in parent class)
        // with a validator and base user form type pre-loaded extension
        // due to data transformer dependency in "BaseUserType" class
        $this->formFactory = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([new BaseUserType($this->dataTransformer)], []))
            ->addExtension(new ValidatorExtension($this->validator))
            ->getFormFactory();
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
                'username' => 'Nom d\'utilisateur modifié',
                'email'    => 'utilisateur-modifie@test.fr',
                'roles'    => 'ROLE_ADMIN, ROLE_USER',
                'password' => [
                    'first'  => 'password_1A$',
                    'second' => 'password_1A$'
                ],
                'isSynchronized' => true,
                'isValid'        => true
            ]
        ];
        yield [
            'Fails when username data is not unique (unique entity constraint)' => [
                'username' => 'olivier.francois_2', // User 2 real username which exists in test database
                'email'    => 'utilisateur-modifie@test.fr',
                'roles'    => 'ROLE_ADMIN, ROLE_USER',
                'password' => [
                    'first'  => 'password_1A$',
                    'second' => 'password_1A$'
                ],
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when email data is not unique (unique entity constraint)' => [
                'username' => 'Nom d\'utilisateur modifié',
                'email'    => 'olivier.francois@gmail.com', // User 2 real email which exists in test database
                'roles'    => 'ROLE_ADMIN, ROLE_USER',
                'password' => [
                    'first'  => 'password_1A$',
                    'second' => 'password_1A$'
                ],
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when username data is blank' => [
                'username' => '',
                'email'    => 'utilisateur-modifie@test.fr',
                'roles'    => 'ROLE_USER',
                'password' => [
                    'first'  => 'password_1A$',
                    'second' => 'password_1A$'
                ],
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when email data is blank' => [
                'username' => 'Nom d\'utilisateur modifié',
                'email'    => '',
                'roles'    => 'ROLE_USER',
                'password' => [
                    'first'  => 'password_1A$',
                    'second' => 'password_1A$'
                ],
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when password data do not match' => [
                'username' => 'Nom d\'utilisateur modifié',
                'email'    => 'utilisateur-modifie@test.fr',
                'roles'    => 'ROLE_USER',
                'password' => [ // "password" is not synchronized!
                    'first'  => 'password_1A$',
                    'second' => 'password_2B$'
                ],
                'isSynchronized' => false,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when username data is not set' => [
                'email'    => 'utilisateur-modifie@test.fr',
                'roles'    => 'ROLE_USER',
                'password' => [
                    'first'  => 'password_1A$',
                    'second' => 'password_1A$'
                ],
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when email data is not set' => [
                'username' => 'Nom d\'utilisateur modifié',
                'roles'    => 'ROLE_USER',
                'password' => [
                    'first'  => 'password_1A$',
                    'second' => 'password_1A$'
                ],
                'isSynchronized' => true,
                'isValid'        => false
            ]
        ];
        yield [
            'Succeeds when roles data is not set' => [
                'username' => 'Nom d\'utilisateur modifié',
                'email'    => 'utilisateur-modifie@test.fr',
                // "ROLE_USER" roles value is set by default in constructor!
                'password' => [
                    'first'  => 'password_1A$',
                    'second' => 'password_1A$'
                ],
                'isSynchronized' => true,
                'isValid'        => true
            ]
        ];
        yield [
            'Fails when password data is not set' => [
                'username' => 'Nom d\'utilisateur modifié',
                'email'    => 'utilisateur-modifie@test.fr',
                'roles'    => 'ROLE_USER',
                'isSynchronized' => false,
                'isValid'        => false
            ]
        ];
    }

    /**
     * Check that data mapping is correctly made when user modification form is submitted.
     *
     * @return void
     */
    public function testSubmittedModifiedUserFormMapping(): void
    {
        // Get existing user with id "1"
        $dataModel = $this->entityManager->getRepository(User::class)->find(1);
        $username = 'utilisateur';
        $email = 'utilisateur@test.fr';
        $roles = ['ROLE_ADMIN', 'ROLE_USER']; // Take care of items order
        $password = 'password_1A$'; // Use an expected format example
        // Clone data model to get the same data automatically set in constructor
        $expectedObject = (clone $dataModel)
            ->setUsername($username)
            ->setEmail($email)
            ->setRoles($roles)
            ->setPassword($password);
        $formData = [
            'user' => [
                'username' => $username,
                'email'    => $email,
                'roles'    => implode($this->dataTransformer->getDelimiter() . ' ', $roles),
                'password' => [
                    'first'  => $password,
                    'second' => $password
                ]
            ]
        ];
        // Create a real form
        $form = $this->createForm(EditUserType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit($formData);
        static::assertEquals($expectedObject, $form->getData());
    }

    /**
     * Check that expected data are validated when user modification form is submitted.
     *
     * @dataProvider provideDataStructureToValidate
     *
     * @param array $data
     *
     * @return void
     */
    public function testSubmittedModifiedUserDataValidation(array $data): void
    {
        // Get existing user with id "1"
        $dataModel = $this->entityManager->getRepository(User::class)->find(1);
        $isValid = $data['isValid'];
        // Use arrow function combined to array filtering with flag based on key
        $formData = array_filter(
            $data,
            fn ($key) => 'isSynchronized' !== $key && 'isValid' !== $key,
            ARRAY_FILTER_USE_KEY
        );
        // Create a real form
        $form = $this->createForm(EditUserType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit(['user' => $formData]);
        static::assertSame($isValid, $form->isValid());
    }

    /**
     * Check that data transformation is correctly made when user modification form is submitted.
     *
     * @dataProvider provideDataStructureToValidate
     *
     * @param array $data
     *
     * @return void
     */
    public function testSubmittedModifiedUserDataTransformation(array $data): void
    {
        // Get existing user with id "1"
        $dataModel = $this->entityManager->getRepository(User::class)->find(1);
        $isSynchronized = $data['isSynchronized'];
        // Use arrow function combined to array filtering with flag based on key
        $formData = array_filter(
            $data,
            fn ($key) => 'isSynchronized' !== $key && 'isValid' !== $key,
            ARRAY_FILTER_USE_KEY
        );
        // Create a real form
        $form = $this->createForm(EditUserType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit(['user' => $formData]);
        $transformationFailure = 0;
        foreach ($form->get('user') as $childForm) {
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
        $this->dataTransformer = null;
        parent::tearDown();
    }
}