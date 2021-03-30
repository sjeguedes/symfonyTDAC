<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Manager;

use App\Entity\User;
use App\Form\Type\CreateUserType;
use App\Form\Type\DeleteUserType;
use App\Form\Type\EditUserType;
use App\Repository\UserRepository;
use App\Tests\Unit\Helpers\CustomAssertionsTestCaseTrait;
use App\View\Builder\UserViewModelBuilder;
use App\View\Builder\ViewModelBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\ResolvedFormType;

/**
 * Class UserViewModelBuilderTest
 *
 * Manage unit tests for user actions view model builder.
 */
class UserViewModelBuilderTest extends TestCase
{
    use CustomAssertionsTestCaseTrait;

    /**
     * @var MockObject|EntityManagerInterface|null
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * @var MockObject|FormFactoryInterface|null
     */
    protected ?FormFactoryInterface $formFactory;

    /**
     * @var ViewModelBuilderInterface|null
     */
    private ?ViewModelBuilderInterface $viewModelBuilder;

    /**
     * @var \StdClass|null
     */
    private ?\StdClass $viewModel;

    /**
     * Get a test user collection.
     *
     * @return array|User[]
     */
    private function getUserCollection(): array
    {
        $user1 = static::createPartialMock(User::class, ['getId']);
        $user2 = static::createPartialMock(User::class, ['getId']);
        $user3 = static::createPartialMock(User::class, ['getId']);
        $user1
            ->method('getId')
            ->willReturn(1);
        $user2
            ->method('getId')
            ->willReturn(2);
        $user3
            ->method('getId')
            ->willReturn(3);

        return [$user1, $user2, $user3];
    }

    /**
     * Get a test user collection scalar set of data.
     *
     * @return array an array of users data without objects hydrating
     */
    private function getUserCollectionScalarData(): array
    {
        $userList = $this->getUserCollection();
        // Put at least users "id" values to be more realistic but other data exist!
        return [
            0 => ['id' => $userList[0]->getId()],
            1 => ['id' => $userList[1]->getId()],
            2 => ['id' => $userList[2]->getId()]
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
        $this->entityManager = static::createMock(EntityManagerInterface::class);
        $this->formFactory = Forms::createFormFactory();
        $this->viewModel = null;
        $this->viewModelBuilder = new UserViewModelBuilder(
            $this->entityManager,
            $this->formFactory
        );
    }

    /**
     * Provide a set of view references to check "form" parameter instance type passed to merged data.
     *
     * @return array
     */
    public function provideViewReferenceToCheckFormInstanceTypeInMergedData(): array
    {
        return [
            'Uses "user deletion" view' => ['delete_user']
        ];
    }

    /**
     * Check that user view model builder cannot create an instance using an unexpected view reference.
     *
     * @return void
     */
    public function testUserViewModelCannotCreateInstanceUsingViewReference(): void
    {
        static::expectException(\RuntimeException::class);
        $this->viewModelBuilder->create('unexpected_view_reference');
    }

    /**
     * Check that view model build fails if at least one merged data key is not of string type.
     *
     * @return void
     */
    public function testUserViewModelCreationIsNotOkWhenMergedDataKeyIsNotOfStringType(): void
    {
        static::expectException(\InvalidArgumentException::class);
        $this->viewModelBuilder->create(null, ['string' => 'value1', 0 => 'value2']);
    }

    /**
     * Check that user view model builder cannot create an instance using wrong "form" merged data instance.
     *
     * Please note that this "form" data is used in user "deletion" views.
     *
     * @dataProvider provideViewReferenceToCheckFormInstanceTypeInMergedData
     *
     * @param string $viewReference
     *
     * @return void
     */
    public function testUserViewModelCannotCreateInstanceUsingWrongFormMergedData(string $viewReference): void
    {
        $entityRepository = static::createPartialMock(UserRepository::class, ['findList']);
        $this->entityManager
            ->expects($this->any())
            ->method('getRepository')
            ->willReturn($entityRepository);
        // Use a custom query method "findList" with scalar result instead of "findAll"!
        // An empty array is sufficient: a User collection is unneeded due to tested exception!
        $entityRepository
            ->expects($this->any())
            ->method('findList')
            ->willReturn([]);
        $testObject = new \stdClass();
        static::expectException(\RuntimeException::class);
        static::expectExceptionMessage(
            sprintf('"form" merged view data must implement %s',FormInterface::class)
        );
        $this->viewModelBuilder->create($viewReference, ['form' => $testObject]);
    }

    /**
     * Check that "user list" view model is correctly built.
     *
     * @return void
     */
    public function testUserListActionViewModelBuildIsOk(): void
    {
        $entityRepository = static::createPartialMock(UserRepository::class, ['findList']);
        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($entityRepository);
        // Use a custom query method "findList" with scalar result instead of "findAll"!
        $entityRepository
            ->expects($this->once())
            ->method('findList')
            ->willReturn($this->getUserCollectionScalarData());
        $viewModel = $this->viewModelBuilder->create('user_list');
        static::assertObjectHasAttribute('users', $viewModel);
        static::assertCount(3, $viewModel->users);
        static::assertObjectHasAttribute('deleteUserFormViews', $viewModel);
        static::assertCount(3, $viewModel->deleteUserFormViews);
        static::assertContainsOnlyInstancesOf(FormView::class, $viewModel->deleteUserFormViews);
    }

    /**
     * Check that "user creation" view model is correctly built.
     *
     * @return void
     */
    public function testUserCreationActionViewModelBuildIsOk(): void
    {
        // Create a real form with a resolved form type instance and form type mock
        // due to data transformer dependency in "BaseUserType" class
        $formType = static::createMock(CreateUserType::class);
        $resolvedFormType = new ResolvedFormType($formType);
        $builder = $resolvedFormType->createBuilder($this->formFactory, 'create_user');
        $currentForm = $builder->getForm();
        $viewModel = $this->viewModelBuilder->create('create_user', ['form' => $currentForm]);
        static::assertObjectNotHasAttribute('form', $viewModel);
        static::assertObjectHasAttribute('createUserFormView', $viewModel);
        static::assertInstanceOf(FormView::class, $viewModel->createUserFormView);
    }

    /**
     * Check that "user update" view model is correctly built.
     *
     * @return void
     */
    public function testUserUpdateActionViewModelBuildIsOk(): void
    {
        $testUserList = $this->getUserCollection();
        $user = $testUserList[0];
        // Create a real form with a resolved form type instance and form type mock
        // due to data transformer dependency in "BaseUserType" class, with "user 1" as data model
        $formType = static::createMock(EditUserType::class);
        $resolvedFormType = new ResolvedFormType($formType);
        $builder = $resolvedFormType->createBuilder($this->formFactory, 'edit_user');
        $currentForm = $builder->getForm();
        $viewModel = $this->viewModelBuilder->create('edit_user', ['form' => $currentForm, 'user' => $user]);
        static::assertObjectNotHasAttribute('form', $viewModel);
        static::assertObjectHasAttribute('editUserFormView', $viewModel);
        static::assertInstanceOf(FormView::class, $viewModel->editUserFormView);
        static::assertObjectHasAttribute('userId', $viewModel);
        static::assertSame(1, $viewModel->userId);
    }


    /**
     * Check that "user deletion" view model is correctly built.
     *
     * @return void
     */
    public function testUserDeletionActionViewModelBuildIsOk(): void
    {
        $entityRepository = static::createPartialMock(UserRepository::class, ['findList']);
        $testUserList = $this->getUserCollection();
        $user = $testUserList[1];
        // Create a real form to ease test with "user 2" as data model
        $currentForm = $this->formFactory->createNamed('delete_user_2', DeleteUserType::class, $user);
        $this->entityManager
            ->expects($this->once())
            ->method('getRepository')
            ->willReturn($entityRepository);
        // Use a custom query method "findList" with scalar result instead of "findAll"!
        $entityRepository
            ->expects($this->once())
            ->method('findList')
            ->willReturn($this->getUserCollectionScalarData());
        // Submit form manually without data pass to it
        $currentForm->submit([]);
        $viewModel = $this->viewModelBuilder->create('delete_user', ['form' => $currentForm]);
        // Other common assertions are already checked in user list view model test!
        static::assertObjectNotHasAttribute('form', $viewModel);
        // Check that submitted deletion form with id "2" had its state preserved in view model
        static::assertTrue($viewModel->deleteUserFormViews[2]->vars['submitted']);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->entityManager = null;
        $this->formFactory = null;
        $this->viewModel = null;
        $this->viewModelBuilder = null;
    }
}