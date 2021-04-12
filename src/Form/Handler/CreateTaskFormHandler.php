<?php

declare(strict_types=1);

namespace App\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\Task;
use App\Form\Type\CreateTaskType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class CreateTaskFormHandler
 *
 * Handle a form in order to create a task.
 */
class CreateTaskFormHandler extends AbstractFormHandler implements FormValidationStateInterface
{
    /**
     * @var DataModelManagerInterface
     */
    private DataModelManagerInterface $taskDataModelManager;

    /**
     * @var TokenStorageInterface
     */
    private TokenStorageInterface $tokenStorage;

    /**
     * CreateTaskFormHandler constructor.
     *
     * @param FormFactoryInterface      $formFactory
     * @param DataModelManagerInterface $taskDataModelManager
     * @param FlashBagInterface         $flashBag
     * @param TokenStorageInterface     $tokenStorage
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        DataModelManagerInterface $taskDataModelManager,
        FlashBagInterface $flashBag,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct($formFactory, 'create_task', CreateTaskType::class, $flashBag);
        $this->taskDataModelManager = $taskDataModelManager;
        $this->tokenStorage = $tokenStorage;
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
        // Associate authenticated user to new task as expected, and save form data
        /** @var Task $task */
        $task = $this->getDataModel();
        $authenticatedUser = $this->tokenStorage->getToken()->getUser();
        // Task was saved correctly!
        if ($this->taskDataModelManager->create($task, $authenticatedUser)) {
            // Store success message in session before redirection
            $this->flashBag->add('success', 'La tâche a bien été ajoutée.');

            return true;
        }
        // Inform that an error happened in process!
        $this->flashBag->add('error', 'Un problème est survenu !');

        return false;
    }
}