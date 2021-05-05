<?php

declare(strict_types=1);

namespace App\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\User;
use App\Form\Type\EditUserType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class EditUserFormHandler
 *
 * Handle a form in order to update a user.
 */
class EditUserFormHandler extends AbstractFormHandler implements FormValidationStateInterface
{
    /**
     * @var DataModelManagerInterface
     */
    private DataModelManagerInterface $userDataModelManager;

    /**
     * @var UserPasswordEncoderInterface
     */
    private UserPasswordEncoderInterface $passwordEncoder;

    /**
     * EditUserFormHandler constructor.
     *
     * @param FormFactoryInterface         $formFactory
     * @param DataModelManagerInterface    $userDataModelManager
     * @param FlashBagInterface            $flashBag
     * @param UserPasswordEncoderInterface $passwordEncoder
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        DataModelManagerInterface $userDataModelManager,
        FlashBagInterface $flashBag,
        UserPasswordEncoderInterface $passwordEncoder
    ) {
        parent::__construct($formFactory, 'edit_user', EditUserType::class, $flashBag);
        $this->userDataModelManager = $userDataModelManager;
        $this->passwordEncoder = $passwordEncoder;
    }

    /**
     * Check if user inputs changed model data.
     *
     * @return bool
     *
     * @see https://stackoverflow.com/questions/5678959/php-check-if-two-arrays-are-equal
     *
     * @throws \Exception
     */
    private function isModelDataContentChanged(): bool
    {
        /** @var UserInterface|User $previousUser */
        $previousUser = $this->getClonedOriginalModel();
        $updatedUser = $this->getDataModel();
        $previousUserData = [
            'username' => $previousUser->getUsername(),
            'email'    => $previousUser->getEmail(),
            'roles'    => $previousUser->getRoles()
        ];
        $updatedUserData = [
            'username' => $updatedUser->getUsername(),
            'email'    => $updatedUser->getEmail(),
            'roles'    => $updatedUser->getRoles()
        ];
        // Form data changed previous user data!
        if (serialize($previousUserData) !== serialize($updatedUserData)) {
            return true;
        }
        // Check if submitted password remained the same
        // Compare password value by comparing hashes before and after form submit
        $newPlainPassword = $updatedUser->getPassword();
        if ($isPasswordUnchanged = $this->passwordEncoder->isPasswordValid($previousUser, $newPlainPassword)) {
            return false;
        }
        // Form data changed password only!
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function execute(array $data = [], bool $isSuccess = null): bool
    {
        // Stop execution if form is not valid
        if (!$isSuccess = $isSuccess ?? $this->isSuccess()) {
            return false;
        }
        // Stop execution if form inputs made no change during POST request!
        if (!$this->isModelDataContentChanged()) {
            $this->flashBag->add('info', 'Aucun changement n\'a été effectué.');

            return false;
        }
        // Update data associated to existing user as expected, by also encoding password if needed,
        // and save form data
        /** @var User $user */
        $user = $this->getDataModel();
        // Encode new password before update
        $encodedPassword = $this->passwordEncoder->encodePassword($user, $user->getPassword());
        // User was updated correctly!
        if ($this->userDataModelManager->update($user, $encodedPassword)) {
            // Store success message in session before redirection
            $this->flashBag->add('success', 'L\'utilisateur a bien été modifié.');

            return true;
        }
        // Inform that an error happened in process!
        $this->flashBag->add('error', 'Un problème est survenu !');

        return false;
    }
}
