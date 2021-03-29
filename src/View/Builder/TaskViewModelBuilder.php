<?php

declare(strict_types=1);

namespace App\View\Builder;

use App\Entity\Task;
use App\Form\Type\DeleteTaskType;
use App\Form\Type\ToggleTaskType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;

/**
 * Class TaskViewModelBuilder
 *
 * Manage task actions view model construction.
 */
class TaskViewModelBuilder extends AbstractViewModelBuilder
{
    /**
     * Define view names.
     */
    private const VIEW_NAMES = [
        'taskList'   => 'task_list',
        'createTask' => 'create_task',
        'editTask'   => 'edit_task',
        'toggleTask' => 'toggle_task',
        'deleteTask' => 'delete_task'
    ];

    /**
     * Define form types which are multiple on the same view.
     */
    public const FORM_TYPES = [
        'toggle_task' => ToggleTaskType::class,
        'delete_task' => DeleteTaskType::class
    ];

    /**
     * {@inheritdoc}
     *
     * @throws \Exception
     */
    protected function addViewData(string $viewReference = null): \Stdclass
    {
        switch ($viewReference) {
            case self::VIEW_NAMES['taskList']:
                $this->prepareTaskListData();
                break;
            case self::VIEW_NAMES['createTask']:
                $this->prepareCreateTaskData();
                break;
            case self::VIEW_NAMES['editTask']:
                $this->prepareEditTaskData();
                break;
            case self::VIEW_NAMES['toggleTask']:
                $this->prepareToggleTaskData();
                break;
            case self::VIEW_NAMES['deleteTask']:
                $this->prepareDeleteTaskData();
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
                sprintf('"form" merged view data must implement %s',FormInterface::class)
            );
        }

        return $currentForm->getConfig()->getType()->getInnerType();
    }

    /**
     * Get task list essential view data.
     *
     * @param bool $isStatusFilterSet
     *
     * @return array
     */
    private function getTaskListViewData(bool $isStatusFilterSet = false): array
    {
        // IMPORTANT: optimize query for performance later!
        $taskListStatus = $isStatusFilterSet ? $this->viewModel->listStatus : null;
        $tasks = $this->entityManager->getRepository(Task::class)->findList($taskListStatus);

        return $tasks;
    }

    /**
     * Prepare "task list" particular view data.
     *
     * @return void
     *
     * @throws \Exception
     */
    private function prepareTaskListData(): void
    {
        // Native function "property_exists" must be used with an instance (not the class due to generic \StdClass)
        $currentForm = property_exists($this->viewModel, 'form') ? $this->viewModel->form : null;
        $currentFormType = null !== $currentForm ? $this->getCurrentFormType($currentForm) : null;
        // Filter form type class name
        $isCurrentToggleForm = null !== $currentFormType && $currentFormType instanceof ToggleTaskType;
        $isCurrentDeletionForm = null !== $currentFormType && $currentFormType instanceof DeleteTaskType;
        $listStatus = property_exists($this->viewModel, 'listStatus') ? $this->viewModel->listStatus : null;
        $isListStatus = null !== $listStatus ? true : false;
        // Get task list
        $tasks = $this->getTaskListViewData($isListStatus);
        $this->viewModel->tasks = $tasks;
        // A current form instance may exist when "toggle task" action is called!
        $this->viewModel->toggleTaskFormViews = $this->generateMultipleFormViews(
            $tasks,
            self::VIEW_NAMES['toggleTask'],
            $isListStatus ? $listStatus : null,
            $isCurrentToggleForm ? $currentForm : null
        );
        // A current form instance may exist when "delete task" action is called!
        $this->viewModel->deleteTaskFormViews = $this->generateMultipleFormViews(
            $tasks,
            self::VIEW_NAMES['deleteTask'],
            $isListStatus ? $listStatus : null,
            $isCurrentDeletionForm ? $currentForm : null
        );
    }

    /**
     * Prepare "create task" particular view data.
     *
     * @return void
     */
    private function prepareCreateTaskData(): void
    {
        $this->viewModel->createTaskFormView = $this->viewModel->form->createView();
        // Delete unnecessary property
        unset($this->viewModel->{'form'});
    }

    /**
     * Prepare "edit task" particular view data.
     *
     * @return void
     */
    private function prepareEditTaskData(): void
    {
        $this->viewModel->editTaskFormView = $this->viewModel->form->createView();
        $this->viewModel->taskId = $this->viewModel->task->getId();
        // Delete unnecessary properties
        unset($this->viewModel->{'form'});
        unset($this->viewModel->{'task'});
    }

    /**
     * Prepare "toggle task" particular view data.
     *
     * @return void
     *
     * @throws \Exception
     */
    private function prepareToggleTaskData(): void
    {
        $this->prepareTaskListData();
        $currentFormType = $this->getCurrentFormType($this->viewModel->form);
        // Delete unnecessary property (since only form view is useful)
        if (null !== $currentFormType && $currentFormType instanceof ToggleTaskType) {
            unset($this->viewModel->{'form'});
        }
    }

    /**
     * Prepare "delete task" particular view data.
     *
     * @return void
     *
     * @throws \Exception
     */
    private function prepareDeleteTaskData(): void
    {
        $this->prepareTaskListData();
        $currentFormType = $this->getCurrentFormType($this->viewModel->form);
        // Delete unnecessary property (since only form view is useful)
        if (null !== $currentFormType && $currentFormType instanceof DeleteTaskType) {
            unset($this->viewModel->{'form'});
        }
    }
}