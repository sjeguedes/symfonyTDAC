<?php

declare(strict_types=1);

namespace App\Form\Handler;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\Task;
use App\Form\Type\DeleteTaskType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

/**
 * Class DeleteTaskFormHandler
 *
 * Handle a form in order to delete a task.
 */
class DeleteTaskFormHandler extends AbstractFormHandler implements FormValidationStateInterface
{
    /**
     * @var DataModelManagerInterface
     */
    private DataModelManagerInterface $taskDataModelManager;

    /**
     * DeleteTaskFormHandler constructor.
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
        parent::__construct($formFactory, 'delete_task', DeleteTaskType::class, $flashBag);
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
        // Remove existing task as expected, and save form data
        /** @var Task $task */
        $task = $this->getDataModel();
        // Task was deleted correctly!
        if ($this->taskDataModelManager->delete($task)) {
            // Store success message in session before redirection
            $this->flashBag->add('success', 'La tâche a bien été supprimée.');

            return true;
        }
        // Inform that an error happened in process!
        $this->flashBag->add('error', 'Un problème est survenu !');

        return false;
    }
}
