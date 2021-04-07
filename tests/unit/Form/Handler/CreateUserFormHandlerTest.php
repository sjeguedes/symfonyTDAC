<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\User;
use App\Form\Handler\CreateUserFormHandler;
use App\Form\Handler\FormHandlerInterface;
use App\Form\Transformer\ArrayToExplodedStringModelTransformer;
use App\Form\Type\Base\BaseUserType;
use App\Tests\Unit\Form\Handler\Helpers\AbstractUserFormHandlerTestCase;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class CreateUserHandlerTest
 *
 * Manage unit tests for user creation form handler.
 */
class CreateUserHandlerKernelTest extends AbstractUserFormHandlerTestCase
{
    /**
     * @var MockObject|FormFactoryInterface|null
     */
    private ?FormFactoryInterface $formFactory;

    /**
     * @var MockObject|FlashBagInterface|null
     */
    private ?FlashBagInterface $flashBag;

    /**
     * @var MockObject|EntityManagerInterface|null
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * @var MockObject|DataModelManagerInterface|null
     */
    private ?DataModelManagerInterface $userDataModelManager;

    /**
     * @var MockObject|UserPasswordEncoderInterface|null
     */
    private ?UserPasswordEncoderInterface $passwordEncoder;

    /**
     * @var FormHandlerInterface|null
     */
    private ?FormHandlerInterface $createUserHandler;

    /**
     * @var DataTransformerInterface|null
     */
    private ?DataTransformerInterface $dataTransformer;

    /**
     * Create a request instance with expected parameters.
     *
     * @param array  $formData
     * @param string $uri
     * @param string $method
     *
     * @return Request
     */
    private function createRequest(
        array $formData = [],
        string $uri = '/users/create',
        string $method = 'POST'
    ): Request {
        // Define default data as valid
        $defaultFormData = [
            'create_user' => [
                'user' => [
                    'username'   => 'utilisateur',
                    'email'      => 'utilisateur@test.fr',
                    'roles'      => 'ROLE_USER',
                    'password'   => [
                        'first'  => 'password_1A$',
                        'second' => 'password_1A$'
                    ]
                ]
            ]
        ];
        $formData = empty($formData) ? $defaultFormData : $formData;
        $request = Request::create(empty($formData) ? '/users/create' : $uri, $method, $formData);

        return $request;
    }


    /**
     * Process a real form made by a form factory instance with capabilities to handle a request.
     *
     * Please note that this method is a helper to refactor code.
     *
     * @param array        $formData
     * @param Request|null $request
     *
     * @return FormInterface
     *
     * @throws \Exception
     */
    private function processForm(array $formData = [], Request $request = null): FormInterface
    {
        $request = $request ?? $this->createRequest($formData);
        // Create a new form handler instance if default request is not used!
        if (!empty($formData) || null !== $request) {
            $formFactoryBuilder = $this->createFormFactoryBuilder($request, User::class);
            $formFactoryBuilder->addExtension(new PreloadedExtension([new BaseUserType($this->dataTransformer)], []));
            $this->formFactory = $formFactoryBuilder->getFormFactory();
            $this->createUserHandler = new createUserFormHandler(
                $this->formFactory,
                $this->userDataModelManager,
                $this->flashBag,
                $this->passwordEncoder
            );
        }
        $form = $this->createUserHandler->process($request, ['dataModel' => new User()]);

        return $form;
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
        // Get a custom "roles" model transformer instance to make form work
        $this->dataTransformer = new ArrayToExplodedStringModelTransformer();
        $formFactoryBuilder = $this->createFormFactoryBuilder($this->createRequest(), User::class);
        $formFactoryBuilder->addExtension(new PreloadedExtension([new BaseUserType($this->dataTransformer)], []));
        $this->formFactory = $formFactoryBuilder->getFormFactory();
        $this->flashBag = static::createMock(FlashBagInterface::class);
        $this->entityManager = static::createPartialMock(EntityManager::class, ['persist', 'flush']);
        $this->userDataModelManager = $this->setUserDataModelManager($this->entityManager);
        $this->passwordEncoder = static::createMock(UserPasswordEncoderInterface::class);
        $this->createUserHandler = new createUserFormHandler(
            $this->formFactory,
            $this->userDataModelManager,
            $this->flashBag,
            $this->passwordEncoder
        );
    }

    /**
     * Provide a set of data to check "execute" method correct return when user creation is not saved.
     *
     * @return \Generator
     */
    public function provideDataToCheckNoExecutionWhenUserIsNotSaved(): \Generator
    {
        yield [
            'Persist throws an exception for user creation' => [
                'persist' => [
                    'exception' => true,
                    'called'    => true
                ],
                'flush' => [
                    'exception' => false,
                    'called'    => false
                ]
            ]
        ];
        yield [
            'Flush throws an exception for user creation' => [
                'persist' => [
                    'exception' => false,
                    'called'    => true
                ],
                'flush' => [
                    'exception' => true,
                    'called'    => true
                ]
            ]
        ];
    }

    /**
     * Check that "execute" method returns true when submitted form is valid.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsTrueWhenValidFormIsProcessed(): void
    {
        // Process a real submitted form with invalid data thanks to helper method.
        $form = $this->processForm();
        $newUser = $form->getData();
        $this->passwordEncoder
            ->expects($this->once())
            ->method('encodePassword')
            ->with($newUser, $newUser->getPassword())
            ->willReturn(
                '$argon2id$v=19$m=65536,t=4,p=1$m3o/' .
                'LtXDliuganMV43ER1w$7kHF1zCwhUOdnkRboZTj0YmGNRodv7Ow0Ht1j5b1fbI'
            );
        $isExecuted = $this->createUserHandler->execute();
        static::assertTrue($isExecuted);
    }

    /**
     * Check that "execute" method returns false when submitted form is invalid.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenInvalidFormIsProcessed(): void
    {
        // Process a real submitted form with invalid data thanks to helper method.
        $this->processForm(
            [
                'create_user' => [
                    'edit_user' => [
                        'username'   => '',
                        'email'      => '',
                        'roles'      => 'ROLE_INEXISTANT',
                        'password'   => [
                            'first'  => 'password_1A$',
                            'second' => 'password_1B$'
                        ]
                    ]
                ]
            ]
        );
        $isExecuted = $this->createUserHandler->execute();
        static::assertFalse($isExecuted);
    }

    /**
     * Check that "execute" method returns true when user creation succeeded.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteMethodReturnsTrueWhenUserCreationPersistenceIsOk(): void
    {
        // Process a real submitted form first with default valid data thanks to helper method.
        $form =$this->processForm();
        $newUser = $form->getData();
        $this->passwordEncoder
            ->expects($this->once())
            ->method('encodePassword')
            ->with($newUser, $newUser->getPassword())
            ->willReturn(
                '$argon2id$v=19$m=65536,t=4,p=1$m3o/' .
                'LtXDliuganMV43ER1w$7kHF1zCwhUOdnkRboZTj0YmGNRodv7Ow0Ht1j5b1fbI'
            );
        $isUserCreationPersisted = $this->createUserHandler->execute();
        static::assertTrue($isUserCreationPersisted);
    }

    /**
     * Check that "execute" method returns false when user creation failed.
     *
     * @dataProvider provideDataToCheckNoExecutionWhenUserIsNotSaved
     *
     * @param array $data
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenUserCreationPersistenceIsNotOk(array $data): void
    {
        // Process a real submitted form first with default valid data thanks to helper method.
        $form = $this->processForm();
        $newUser = $form->getData();
        $this->passwordEncoder
            ->expects($this->once())
            ->method('encodePassword')
            ->with($newUser, $newUser->getPassword())
            ->willReturn(
                '$argon2id$v=19$m=65536,t=4,p=1$m3o/' .
                'LtXDliuganMV43ER1w$7kHF1zCwhUOdnkRboZTj0YmGNRodv7Ow0Ht1j5b1fbI'
            );
        // Throw an exception to be more realistic (when database persistence fails)
        // in order to make the test behave as expected
        $this->entityManager
            ->expects($data['persist']['called'] ? $this->once() : $this->any())
            ->method('persist')
            ->willReturnCallback(function () use ($data) {
                // Make "persist" throw an exception
                if ($data['persist']['exception']) throw new \Exception();
            });
        $this->entityManager
            ->expects($data['flush']['called'] ? $this->once() : $this->any())
            ->method('flush')
            ->willReturnCallback(function () use ($data) {
                // Make "flush" throw an exception
                if ($data['flush']['exception']) throw new \Exception();
            });
        $isUserCreationPersisted = $this->createUserHandler->execute();
        static::assertFalse($isUserCreationPersisted);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->dataTransformer = null;
        $this->formFactory = null;
        $this->flashBag = null;
        $this->entityManager = null;
        $this->userDataModelManager = null;
        $this->passwordEncoder = null;
        $this->createUserHandler = null;
        parent::tearDown();
    }
}