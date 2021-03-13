<?php

declare(strict_types=1);

namespace App\Form\Handler;

use App\Entity\Manager\ModelManagerInterface;
use App\Entity\Task;
use App\Form\Type\ToggleTaskType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Class ToggleTaskFormHandler
 *
 * Handle a form in order to update a task.
 */
class ToggleTaskFormHandler extends AbstractFormHandler implements FormValidationStateInterface
{
    /**
     * @var ModelManagerInterface
     */
    private ModelManagerInterface $taskManager;

    /**
     * EditTaskFormHandler constructor.
     *
     * @param FormFactoryInterface  $formFactory
     * @param ModelManagerInterface $taskManager
     * @param FlashBagInterface     $flashBag
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        ModelManagerInterface $taskManager,
        FlashBagInterface $flashBag
    ) {
        parent::__construct($formFactory, ToggleTaskType::class, $flashBag);
        $this->taskManager = $taskManager;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    public function execute(array $data = [], bool $isSuccess = null): bool
    {
        // Stop execution if form is not valid (kept not needed at this time!)
        if (!$isSuccess = $isSuccess ?? $this->isSuccess()) {
            return false;
        }
        // Change existing task "isDone" state as expected, and save form data
        /** @var Task $task */
        $task = $this->getDataModel();
        // Task was updated correctly!
        if ($this->taskManager->toggle($task)) {
            // Store success message in session before redirection
            $this->flashBag->add(
                'success',
                sprintf(
                    'La tâche "%s" a bien été marquée comme ' . ($task->isDone() ? 'faite.' : 'non terminée.'),
                    $task->getTitle()
                )
            );

            return true;
        }

        return false;
    }
}