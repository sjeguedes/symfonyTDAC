<?php

declare(strict_types=1);

namespace App\View\Builder;

use App\Entity\User;
use App\Form\Type\DeleteUserType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Class UserViewModelBuilder
 *
 * Manage user actions view model construction.
 */
class UserViewModelBuilder extends AbstractViewModelBuilder
{
    /**
     * Define view names.
     */
    private const VIEW_NAMES = [
        'userList'   => 'user_list',
        'createUser' => 'create_user',
        'editUser'   => 'edit_user',
        'deleteUser' => 'delete_user'
    ];

    /**
     * Define form types which are multiple on the same view.
     */
    public const FORM_TYPES = [
        'delete_user' => DeleteUserType::class
    ];

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function addViewData(string $viewReference = null): \Stdclass
    {
        switch ($viewReference) {
            case self::VIEW_NAMES['userList']:
                $this->prepareUserListData();
                break;
            case self::VIEW_NAMES['createUser']:
                $this->prepareCreateUserData();
                break;
            case self::VIEW_NAMES['editUser']:
                $this->prepareEditUserData();
                break;
            case self::VIEW_NAMES['deleteUser']:
                $this->prepareDeleteUserData();
                break;
            default:
                if (null !== $viewReference) {
                    throw new \RuntimeException('Incorrect reference: no corresponding view was found!');
                }
        }

        return $this->viewModel;
    }

    /**
     * Get current submitted form type instance.
     *
     * @param object $currentForm
     *
     * @return FormTypeInterface
     *
     * @throws \Exception
     */
    private function getCurrentFormType(object $currentForm): FormTypeInterface
    {
        // Check form expected instance
        /** @var  FormInterface $currentForm */
        if (!$currentForm instanceof FormInterface) {
            throw new \RuntimeException(
                sprintf('"form" merged view data must implement %s', FormInterface::class)
            );
        }

        return $currentForm->getConfig()->getType()->getInnerType();
    }

    /**
     * Get user list essential view data.
     *
     * @return array
     */
    private function getUserListViewData(): array
    {
        $users = $this->entityManager->getRepository(User::class)->findList();

        return $users;
    }

    /**
     * Prepare "user list" particular view data.
     *
     * @return void
     *
     * @throws \Exception
     */
    private function prepareUserListData(): void
    {
        // Native function "property_exists" must be used with an instance (not the class due to generic \StdClass)
        $currentForm = property_exists($this->viewModel, 'form') ? $this->viewModel->form : null;
        $currentFormType = null !== $currentForm ? $this->getCurrentFormType($currentForm) : null;
        // Filter form type class name
        $isCurrentDeletionForm = null !== $currentFormType && $currentFormType instanceof DeleteUserType;
        // Get user list
        $users = $this->getUserListViewData();
        $this->viewModel->users = $users;
        // A current form instance may exist when "delete user" action is called!
        $this->viewModel->deleteUserFormViews = $this->generateMultipleFormViews(
            $users,
            self::VIEW_NAMES['deleteUser'],
            null,
            $isCurrentDeletionForm ? $currentForm : null
        );
    }

    /**
     * Prepare "create user" particular view data.
     *
     * @return void
     */
    private function prepareCreateUserData(): void
    {
        $this->viewModel->createUserFormView = $this->viewModel->form->createView();
        // Delete unnecessary property
        unset($this->viewModel->{'form'});
    }

    /**
     * Prepare "edit user" particular view data.
     *
     * @return void
     */
    private function prepareEditUserData(): void
    {
        $this->viewModel->editUserFormView = $this->viewModel->form->createView();
        $this->viewModel->userId = $this->viewModel->user->getId();
        $this->viewModel->username = $this->viewModel->user->getUsername();
        // Delete unnecessary properties
        unset($this->viewModel->{'form'});
        unset($this->viewModel->{'user'});
    }

    /**
     * Prepare "delete user" particular view data.
     *
     * @return void
     *
     * @throws \Exception
     */
    private function prepareDeleteUserData(): void
    {
        $this->prepareUserListData();
        $currentFormType = $this->getCurrentFormType($this->viewModel->form);
        // Delete unnecessary property (since only form view is useful)
        if (null !== $currentFormType && $currentFormType instanceof DeleteUserType) {
            unset($this->viewModel->{'form'});
        }
    }
}
