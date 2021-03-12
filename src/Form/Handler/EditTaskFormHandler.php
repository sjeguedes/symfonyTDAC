<?php

declare(strict_types=1);

namespace App\Form\Handler;

use App\Entity\Manager\ModelManagerInterface;
use App\Entity\Task;
use App\Form\Type\EditTaskType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Class EditTaskFormHandler
 *
 * Handle a form in order to update a task.
 */
class EditTaskFormHandler extends AbstractFormHandler implements FormValidationStateInterface
{
    /**
     * @var ModelManagerInterface
     */
    private ModelManagerInterface $taskManager;

    /**
     * @var TokenStorageInterface
     */
    private TokenStorageInterface $tokenStorage;

    /**
     * EditTaskFormHandler constructor.
     *
     * @param FormFactoryInterface  $formFactory
     * @param ModelManagerInterface $taskManager
     * @param FlashBagInterface     $flashBag
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        ModelManagerInterface $taskManager,
        FlashBagInterface $flashBag,
        TokenStorageInterface $tokenStorage
    ) {
        parent::__construct($formFactory, EditTaskType::class, $flashBag);
        $this->taskManager = $taskManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Check if user inputs changed model data.
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function isModelDataContentChanged(): bool
    {
        $previousTask = $this->getClonedOriginalModel();
        $updatedTask = $this->getDataModel();
        // Compare the two objects to evaluate change(s)
        return $previousTask != $updatedTask;
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
        // Associate authenticated user as last editor (author is locked!) to existing task as expected,
        // and save form data
        /** @var Task $task */
        $task = $this->getDataModel();
        $authenticatedUser = $this->tokenStorage->getToken()->getUser();
        // Task was updated correctly!
        if ($this->taskManager->update($task, $authenticatedUser)) {
            // Store success message in session before redirection
            $this->flashBag->add('success', 'La tâche a bien été modifiée.');

            return true;
        }

        return false;
    }
}