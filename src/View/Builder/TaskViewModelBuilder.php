<?php

declare(strict_types=1);

namespace App\View\Builder;

use App\Entity\Task;
use App\Form\Type\ToggleTaskType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

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
     * {@inheritdoc}
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
     * Generate all "toggle" form views.
     *
     * @param array              $tasks
     * @param FormInterface|null $currentForm
     *
     * @return array|FormView[]
     */
    private function generateToggleTaskFormViews(array $tasks, FormInterface $currentForm = null): array
    {
        $toggleFormViews = [];
        $length = \count($tasks);
        $formNamePrefix = self::VIEW_NAMES['toggleTask'] . '_';
        // Current (submitted) task toggle form name does not contain an integer as suffix!
        if (null !== $currentForm && !preg_match('/(\d+)$/', $currentForm->getName(), $matches)) {
            throw new \RuntimeException("Current form name suffix is expected to be an integer as index!");
        }
        $suffixIdAsInt = isset($matches) && 2 === \count($matches) ? $matches[1] : null;
        for ($i = 0; $i < $length; $i++) {
            $taskId = $tasks[$i]->getId();
            // Create current (submitted) form view if not null with its actual state!
            if (null !== $suffixIdAsInt && $taskId === \intval($suffixIdAsInt)) {
                $toggleFormViews[$taskId] = $currentForm->createView();
                continue;
            }
            // Create other identical forms views
            $formView = $this->formFactory->createNamed(
                $formNamePrefix . $taskId,
                ToggleTaskType::class
            );
            $toggleFormViews[$taskId] = $formView->createView();
        }
        return $toggleFormViews;
    }

    /**
     * Prepare "task list" particular view data.
     *
     * @return void
     */
    private function prepareTaskListData(): void
    {
        // IMPORTANT: optimize query for performance later!
        $tasks = $this->entityManager->getRepository(Task::class)->findAll();
        $this->viewModel->tasks = $tasks;
        // Native function "property_exists" must be used with an instance (not the class due to generic \StdClass)
        $currentForm = property_exists($this->viewModel, 'form') ? $this->viewModel->form : null;
        // A current form instance exists when "toggle task" action is called!
        $this->viewModel->toggleTaskFormViews = $this->generateToggleTaskFormViews(
            $tasks,
            $currentForm
        );
        // IMPORTANT: add delete form views later here!
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
     */
    private function prepareToggleTaskData(): void
    {
        $this->prepareTaskListData();
        // Delete unnecessary property (since only form view is useful)
        unset($this->viewModel->{'form'});
    }

    /**
     * Prepare "delete task" particular view data.
     *
     * @return void
     */
    private function prepareDeleteTaskData(): void
    {
        // IMPORTANT: change this method script here later if necessary!
        $this->prepareTaskListData();
    }
}