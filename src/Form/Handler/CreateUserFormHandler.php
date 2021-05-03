<?php

declare(strict_types=1);

namespace App\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\User;
use App\Form\Type\CreateUserType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class CreateUserFormHandler
 *
 * Handle a form in order to create a user.
 */
class CreateUserFormHandler extends AbstractFormHandler implements FormValidationStateInterface
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
     * CreateUserFormHandler constructor.
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
        parent::__construct($formFactory, 'create_user', CreateUserType::class, $flashBag);
        $this->userDataModelManager = $userDataModelManager;
        $this->passwordEncoder = $passwordEncoder;
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
        // Create data associated to new user as expected, by also encoding password,
        // and save form data
        /** @var User $user */
        $user = $this->getDataModel();
        // Encode new password before creation
        $encodedPassword = $this->passwordEncoder->encodePassword($user, $user->getPassword());
        // User was saved correctly!
        if ($this->userDataModelManager->create($user, $encodedPassword)) {
            // Store success message in session before redirection
            $this->flashBag->add('success', 'L\'utilisateur a bien été ajouté.');

            return true;
        }
        // Inform that an error happened in process!
        $this->flashBag->add('error', 'Un problème est survenu !');

        return false;
    }
}
