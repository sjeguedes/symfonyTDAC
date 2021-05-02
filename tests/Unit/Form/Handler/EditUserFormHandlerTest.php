<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\User;
use App\Form\Handler\EditUserFormHandler;
use App\Form\Handler\FormHandlerInterface;
use App\Form\Transformer\ArrayToExplodedStringModelTransformer;
use App\Form\Type\Base\BaseUserType;
use App\Tests\Unit\Form\Handler\Helpers\AbstractUserFormHandlerTestCase;
use App\Tests\Unit\Helpers\EntityReflectionTestCaseTrait;
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
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class EditUserFormHandlerTest
 *
 * Manage unit tests for user update form handler.
 */
class EditUserFormHandlerTest extends AbstractUserFormHandlerTestCase
{
    use EntityReflectionTestCaseTrait;

    /**
     * UserInterface|User|null
     */
    private ?UserInterface $defaultDataModel;

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
    private ?FormHandlerInterface $editUserHandler;

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
        string $uri = '/users/1/edit',
        string $method = 'POST'
    ): Request {
        // Define default data as valid
        $defaultFormData = [
            'edit_user' => [
                'user' => [
                    'username'   => 'utilisateur modifié',
                    'email'      => 'utilisateur-modifie@test.fr',
                    'roles'      => 'ROLE_ADMIN, ROLE_USER',
                    // Keep the same password as default model data to ease tests
                    'password'   => [
                        'first'  => 'password_1A$',
                        'second' => 'password_1A$'
                    ]
                ]
            ]
        ];
        $formData = empty($formData) ? $defaultFormData : $formData;
        $request = Request::create(empty($formData) ? '/users/1/edit' : $uri, $method, $formData);

        return $request;
    }

    /**
     * Get data model as default user for these tests.
     *
     * @return UserInterface
     *
     * @throws \Exception
     */
    private function getDefaultUserDataModel(): UserInterface
    {
        $existingUser = (new User())
            ->setUsername('utilisateur')
            ->setEmail('utilisateur@test.fr')
            ->setRoles(['ROLE_USER'])
            // Plain password value is "password_1A$".
            ->setPassword(
                '$argon2id$v=19$m=65536,t=4,p=1$m3o/' .
                'LtXDliuganMV43ER1w$7kHF1zCwhUOdnkRboZTj0YmGNRodv7Ow0Ht1j5b1fbI'
            );
        // Use reflection to get a fake existing user with id
        /** @var UserInterface $existingUser */
        $existingUser = $this->setEntityIdByReflection($existingUser, 1);
        return $existingUser;
    }

    /**
     * Process a real form made by a form factory instance with capabilities to handle a request.
     *
     * Please note that this method is a helper to refactor code.
     *
     * @param array        $formData a mandatory set of data for update form to compare real change(s)
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
        if (null !== $request) {
            $formFactoryBuilder = $this->createFormFactoryBuilder($request, User::class);
            $formFactoryBuilder->addExtension(new PreloadedExtension([new BaseUserType($this->dataTransformer)], []));
            $this->formFactory = $formFactoryBuilder->getFormFactory();
            $this->editUserHandler = new EditUserFormHandler(
                $this->formFactory,
                $this->userDataModelManager,
                $this->flashBag,
                $this->passwordEncoder
            );
        }
        $existingUser = $this->defaultDataModel;
        $form = $this->editUserHandler->process($request, ['dataModel' => $existingUser]);

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
        $this->defaultDataModel = $this->getDefaultUserDataModel();
        // Get a custom "roles" model transformer instance to make form work
        $this->dataTransformer = new ArrayToExplodedStringModelTransformer();
        $formFactoryBuilder = $this->createFormFactoryBuilder($this->createRequest(), User::class);
        $formFactoryBuilder->addExtension(new PreloadedExtension([new BaseUserType($this->dataTransformer)], []));
        $this->formFactory = $formFactoryBuilder->getFormFactory();
        $this->flashBag = static::createMock(FlashBagInterface::class);
        $this->entityManager = static::createPartialMock(EntityManager::class, ['flush']);
        $this->userDataModelManager = $this->setUserDataModelManager($this->entityManager);
        $this->passwordEncoder = static::createMock(UserPasswordEncoderInterface::class);
        $this->editUserHandler = new EditUserFormHandler(
            $this->formFactory,
            $this->userDataModelManager,
            $this->flashBag,
            $this->passwordEncoder
        );
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
        $updatedUser = $form->getData();
        $this->passwordEncoder
            ->expects($this->once())
            ->method('encodePassword')
            ->with($updatedUser, $updatedUser->getPassword())
            // Plain password "password_1A$" is unchanged to simplify this test!
            ->willReturn(
                '$argon2id$v=19$m=65536,t=4,p=1$m3o/' .
                'LtXDliuganMV43ER1w$7kHF1zCwhUOdnkRboZTj0YmGNRodv7Ow0Ht1j5b1fbI'
            );
        $isExecuted = $this->editUserHandler->execute();
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
        );
        $isExecuted = $this->editUserHandler->execute();
        static::assertFalse($isExecuted);
    }

    /**
     * Check that "execute" method returns true when user update succeeded.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteMethodReturnsTrueWhenUserUpdateFlushIsOk(): void
    {
        // Process a real submitted form first with default valid data thanks to helper method.
        $form = $this->processForm();
        $updatedUser = $form->getData();
        $this->passwordEncoder
            ->expects($this->never())
            ->method('isPasswordValid');
        $this->passwordEncoder
            ->expects($this->once())
            ->method('encodePassword')
            ->with($updatedUser, $updatedUser->getPassword())
            ->willReturn(
                '$argon2id$v=19$m=65536,t=4,p=1$m3o/' .
                'LtXDliuganMV43ER1w$7kHF1zCwhUOdnkRboZTj0YmGNRodv7Ow0Ht1j5b1fbI'
            );
        $isUserUpdateFlushed = $this->editUserHandler->execute();
        static::assertTrue($isUserUpdateFlushed);
    }

    /**
     * Check that "execute" method returns false when user update failed.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenUserUpdateFlushIsNotOk(): void
    {
        // Process a real submitted form first with default valid data thanks to helper method.
        $form = $this->processForm();
        $updatedUser = $form->getData();
        $this->passwordEncoder
            ->expects($this->never())
            ->method('isPasswordValid');
        $this->passwordEncoder
            ->expects($this->once())
            ->method('encodePassword')
            ->with($updatedUser, $updatedUser->getPassword())
            // Password value is "password_1A$".
            ->willReturn(
                '$argon2id$v=19$m=65536,t=4,p=1$m3o/' .
                'LtXDliuganMV43ER1w$7kHF1zCwhUOdnkRboZTj0YmGNRodv7Ow0Ht1j5b1fbI'
            );
        // Throw an exception to be more realistic (when database persistence fails)
        // in order to make the test behave as expected
        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception());
        $isUserUpdateFlushed = $this->editUserHandler->execute();
        static::assertFalse($isUserUpdateFlushed);
    }

    /**
     * Check that "execute" method returns false when user update is submitted with no change.
     *
     * Please note that it is a kind of feature which aims at improving user experience.
     * "No change" means data in form are equals to initial data.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenUserUpdateMakesNoChange(): void
    {
        $previousUser = clone($this->defaultDataModel);
        // Process a real submitted form with valid data thanks to helper method.
        // No change is submitted since default model data are re-injected!
        $form = $this->processForm(
            [
                'edit_user' => [
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
            ]
        );
        $updatedUser = $form->getData();
        $this->passwordEncoder
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($previousUser, $updatedUser->getPassword())
            ->willReturn(true);
        $this->passwordEncoder
            ->expects($this->never())
            ->method('encodePassword');
        $isUserUpdateFlushed = $this->editUserHandler->execute();
        static::assertFalse($isUserUpdateFlushed);
    }

    /**
     * Check that "execute" method returns true when user update is submitted
     * with change which concerns password value only.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsTrueWhenUserUpdateChangesPasswordOnly(): void
    {
        $previousUser = clone($this->defaultDataModel);
        // Process a real submitted form with valid data thanks to helper method.
        // Change on password is submitted, and default model data are re-injected!
        $form = $this->processForm(
            [
                'edit_user' => [
                    'user' => [
                        'username'   => 'utilisateur',
                        'email'      => 'utilisateur@test.fr',
                        'roles'      => 'ROLE_USER',
                        // Previous password value was "password_1A$".
                        'password'   => [
                            'first'  => 'password_2B$',
                            'second' => 'password_2B$'
                        ]
                    ]
                ]
            ]
        );
        $updatedUser = $form->getData();
        $this->passwordEncoder
            ->expects($this->once())
            ->method('isPasswordValid')
            ->with($previousUser, $updatedUser->getPassword())
            ->willReturn(false);
        $this->passwordEncoder
            ->expects($this->once())
            ->method('encodePassword')
            ->with($updatedUser, $updatedUser->getPassword())
            // Password value is "password_2B$".
            ->willReturn(
                '$argon2id$v=19$m=65536,t=4,p=1$/' .
                'jjOf9y3SWaJFzSNmLR7eA$HNVuQqDIr56OXv/s8AWpE7L5Hm4iUJ13C5ivtZbQDnA'
            );
        $isUserUpdateFlushed = $this->editUserHandler->execute();
        static::assertTrue($isUserUpdateFlushed);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->defaultDataModel = null;
        $this->dataTransformer = null;
        $this->formFactory = null;
        $this->flashBag = null;
        $this->entityManager = null;
        $this->userDataModelManager = null;
        $this->passwordEncoder = null;
        $this->editUserHandler = null;
        parent::tearDown();
    }
}