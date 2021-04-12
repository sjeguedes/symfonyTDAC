<?php

declare(strict_types=1);

namespace App\Tests\Integration\Form\Type;

use App\Entity\User;
use App\Form\Transformer\ArrayToExplodedStringModelTransformer;
use App\Form\Type\Base\BaseUserType;
use App\Form\Type\CreateUserType;
use App\Tests\Integration\Form\Type\Helpers\AbstractFormTypeKernelTestCase;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;

/**
 * Class CreateUserTypeTest
 *
 * Manage integration tests for user creation form type.
 */
class CreateUserTypeTest extends AbstractFormTypeKernelTestCase
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
                'username' => 'utilisateur',
                'email'    => 'utilisateur@test.fr',
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
                'username' => 'daniel.lecomte_1', // User 1 real username which exists in test database
                'email'    => 'utilisateur@test.fr',
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
                'username' => 'utilisateur',
                'email'    => 'daniel.lecomte@club-internet.fr', // User 1 real email which exists in test database
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
                'email'    => 'utilisateur@test.fr',
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
                'username' => 'utilisateur',
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
            'Fails when roles data is tampered' => [
                'username' => 'utilisateur',
                'email'    => 'utilisateur@test.fr',
                'roles'    => 'ROLE_INEXISTANT', // "roles" is not synchronized!
                'password' => [
                    'first'  => 'password_1A$',
                    'second' => 'password_1A$'
                ],
                'isSynchronized' => false,
                'isValid'        => false
            ]
        ];
        yield [
            'Fails when password data do not match' => [
                'username' => 'utilisateur',
                'email'    => 'utilisateur@test.fr',
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
                'email'    => 'utilisateur@test.fr',
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
                'username' => 'utilisateur',
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
                'username' => 'utilisateur',
                'email'    => 'utilisateur@test.fr',
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
                'username' => 'utilisateur',
                'email'    => 'utilisateur@test.fr',
                'roles'    => 'ROLE_USER',
                'isSynchronized' => false,
                'isValid'        => false
            ]
        ];
    }

    /**
     * Check that data mapping is correctly made when user creation form is submitted.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedNewUserFormMapping(): void
    {
        $dataModel = new User();
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
        $form = $this->createForm(CreateUserType::class, $dataModel);
        $form->submit($formData);
        static::assertEquals($expectedObject, $form->getData());
    }

    /**
     * Check that expected data are validated when user creation form is submitted.
     *
     * @dataProvider provideDataStructureToValidate
     *
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedNewUserDataValidation(array $data): void
    {
        $dataModel = new User();
        $isValid = $data['isValid'];
        // Use arrow function combined to array filtering with flag based on key
        $formData = array_filter(
            $data,
            fn ($key) => 'isSynchronized' !== $key && 'isValid' !== $key,
            ARRAY_FILTER_USE_KEY
        );
        // Create a real form
        $form = $this->createForm(CreateUserType::class, $dataModel);
        // "Simulate" submitted form data provided by a request
        $form->submit(['user' => $formData]);
        static::assertSame($isValid, $form->isValid());
    }

    /**
     * Check that data transformation is correctly made when user creation form is submitted.
     *
     * @dataProvider provideDataStructureToValidate
     *
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testSubmittedNewUserDataTransformation(array $data): void
    {
        $dataModel = new User();
        $isSynchronized = $data['isSynchronized'];
        // Use arrow function combined to array filtering with flag based on key
        $formData = array_filter(
            $data,
            fn ($key) => 'isSynchronized' !== $key && 'isValid' !== $key,
            ARRAY_FILTER_USE_KEY
        );
        // Create a real form
        $form = $this->createForm(CreateUserType::class, $dataModel);
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