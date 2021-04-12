<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\User;
use App\Form\Handler\DeleteUserFormHandler;
use App\Form\Handler\FormHandlerInterface;
use App\Tests\Unit\Form\Handler\Helpers\AbstractUserFormHandlerTestCase;
use App\Tests\Unit\Helpers\EntityReflectionTestCaseTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Class DeleteUserFormHandlerTest
 *
 * Manage unit tests for user deletion form handler.
 */
class DeleteUserFormHandlerTest extends AbstractUserFormHandlerTestCase
{
    use EntityReflectionTestCaseTrait;

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
     * @var FormHandlerInterface|null
     */
    private ?FormHandlerInterface $deleteUserHandler;

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
        string $uri = '/users/1/delete',
        string $method = 'DELETE'
    ): Request {
        // Define default data as valid
        $defaultFormData = [
            'delete_user_1' => [
                // No fields are set at this time!
            ]
        ];
        $formData = empty($formData) ? $defaultFormData : $formData;
        $request = Request::create(empty($formData) ? '/users/1/delete' : $uri, $method, $formData);

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
        if (null !== $request) {
            $this->formFactory = $this->buildFormFactory($request, User::class);
            $this->deleteUserHandler = new DeleteUserFormHandler(
                $this->formFactory,
                $this->userDataModelManager,
                $this->flashBag
            );
        }
        // Use reflection to get a fake existing user with id
        $existingUser = (new User())
            ->setUsername('utilisateur')
            ->setEmail('utilisateur@test.fr')
            ->setRoles(['ROLE_USER'])
            // Plain password value is "password_1A$".
            ->setPassword(
                '$argon2id$v=19$m=65536,t=4,p=1$m3o/' .
                'LtXDliuganMV43ER1w$7kHF1zCwhUOdnkRboZTj0YmGNRodv7Ow0Ht1j5b1fbI'
            );
        $existingUser = $this->setEntityIdByReflection($existingUser, 1);
        $form = $this->deleteUserHandler->process($request, ['dataModel' => $existingUser]);
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
        $this->formFactory = $this->buildFormFactory($this->createRequest(), User::class);
        $this->flashBag = static::createMock(FlashBagInterface::class);
        $this->entityManager = static::createPartialMock(EntityManager::class, ['remove', 'flush']);
        $this->userDataModelManager = $this->setUserDataModelManager($this->entityManager);
        $this->deleteUserHandler = new DeleteUserFormHandler(
            $this->formFactory,
            $this->userDataModelManager,
            $this->flashBag
        );
    }

    /**
     * Check that "delete" method returns false when submitted form is invalid.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenInvalidFormIsProcessed(): void
    {
        // Process a real submitted form with invalid data thanks to helper method.
        // No field is set, and no data is expected at this time!
        $this->processForm(
            ['delete_user_1' => ['unexpected' => 'Test']] // Use real field(s) later if needed
        );
        $isExecuted = $this->deleteUserHandler->execute();
        static::assertFalse($isExecuted);
    }

    /**
     * Check that "execute" method returns true when user deletion succeeded.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteMethodReturnsTrueWhenUserDeletionFlushIsOk(): void
    {
        // Process a real submitted form first with default valid data thanks to helper method.
        $this->processForm();
        $isUserDeletionFlushed = $this->deleteUserHandler->execute();
        static::assertTrue($isUserDeletionFlushed);
    }

    /**
     * Check that "execute" method returns false when user deletion failed.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testExecuteReturnsFalseWhenUserUserDeletionFlushIsNotOk(): void
    {
        // Process a real submitted form first with default valid data thanks to helper method.
        $this->processForm();
        // Throw an exception to be more realistic (when database persistence fails)
        // in order to make the test behave as expected
        $this->entityManager
            ->expects($this->once())
            ->method('flush')
            ->willThrowException(new \Exception());
        $isUserDeletionFlushed = $this->deleteUserHandler->execute();
        static::assertFalse($isUserDeletionFlushed);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->formFactory = null;
        $this->flashBag = null;
        $this->entityManager = null;
        $this->userDataModelManager = null;
        $this->deleteUserHandler = null;
        parent::tearDown();
    }
}