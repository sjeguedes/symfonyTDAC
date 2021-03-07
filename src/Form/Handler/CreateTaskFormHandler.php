<?php

declare(strict_types=1);

namespace App\Form\Handler;

use App\Entity\Manager\ModelManagerInterface;
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
     * @var ModelManagerInterface
     */
    private ModelManagerInterface $taskManager;

    /**
     * @var TokenStorageInterface
     */
    private TokenStorageInterface $tokenStorage;

    /**
     * CreateTaskFormHandler constructor.
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
        parent::__construct($formFactory, CreateTaskType::class, $flashBag);
        $this->taskManager = $taskManager;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function execute(bool $isSuccess = null, array $data = []): bool
    {
        if ($isSuccess = $isSuccess ?? $this->isSuccess()) {
            // Associate authenticated user to new task as expected, and save form data
            /** @var Task $task */
            $task = $this->getDataModel();
            $authenticatedUser = $this->tokenStorage->getToken()->getUser();
            // Task was saved correctly!
            if ($this->taskManager->create($task, $authenticatedUser)) {
                // Store success message in session before redirection
                $this->flashBag->add('success', 'La tâche a bien été ajoutée.');

                return true;
            }
        }

        return false;
    }
}