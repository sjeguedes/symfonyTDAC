<?php

declare(strict_types=1);

namespace App\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\User;
use App\Form\Type\DeleteUserType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Class DeleteUserFormHandler
 *
 * Handle a form in order to delete a user.
 */
class DeleteUserFormHandler extends AbstractFormHandler implements FormValidationStateInterface
{
    /**
     * @var DataModelManagerInterface
     */
    private DataModelManagerInterface $userDataModelManager;

    /**
     * DeleteUserFormHandler constructor.
     *
     * @param FormFactoryInterface      $formFactory
     * @param DataModelManagerInterface $userDataModelManager
     * @param FlashBagInterface         $flashBag
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        DataModelManagerInterface $userDataModelManager,
        FlashBagInterface $flashBag
    ) {
        parent::__construct($formFactory, 'delete_user', DeleteUserType::class, $flashBag);
        // Multiple identical forms of same type will be displayed!
        $this->isFormNameIndexed = true;
        $this->userDataModelManager = $userDataModelManager;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function execute(array $data = [], bool $isSuccess = null): bool
    {
        // Stop execution if form is not valid (kept but not needed at this time!)
        if (!$isSuccess = $isSuccess ?? $this->isSuccess()) {
            return false;
        }
        // Remove existing user as expected, and save form data
        /** @var User $user */
        $user = $this->getDataModel();
        // User was deleted correctly!
        if ($this->userDataModelManager->delete($user)) {
            // Store success message in session before redirection
            $this->flashBag->add('success', 'L\'utilisateur a bien été supprimé.');

            return true;
        }
        // Inform that an error happened in process!
        $this->flashBag->add('error', 'Un problème est survenu !');

        return false;
    }
}