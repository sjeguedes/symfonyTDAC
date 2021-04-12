<?php

declare(strict_types=1);

namespace App\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\Task;
use App\Form\Type\ToggleTaskType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Class ToggleTaskFormHandler
 *
 * Handle a form in order to toggle a task "isDone" state.
 */
class ToggleTaskFormHandler extends AbstractFormHandler implements FormValidationStateInterface
{
    /**
     * @var DataModelManagerInterface
     */
    private DataModelManagerInterface $taskDataModelManager;

    /**
     * ToggleTaskFormHandler constructor.
     *
     * @param FormFactoryInterface      $formFactory
     * @param DataModelManagerInterface $taskDataModelManager
     * @param FlashBagInterface         $flashBag
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        DataModelManagerInterface $taskDataModelManager,
        FlashBagInterface $flashBag
    ) {
        parent::__construct($formFactory, 'toggle_task', ToggleTaskType::class, $flashBag);
        // Multiple identical forms of same type will be displayed!
        $this->isFormNameIndexed = true;
        $this->taskDataModelManager = $taskDataModelManager;
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
        // Change existing task "isDone" state as expected, and save form data
        /** @var Task $task */
        $task = $this->getDataModel();
        // Task was updated correctly!
        if ($this->taskDataModelManager->toggle($task)) {
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
        // Inform that an error happened in process!
        $this->flashBag->add('error', 'Un problème est survenu !');

        return false;
    }
}