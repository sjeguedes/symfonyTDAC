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
    protected function addViewData(string $viewReference): \Stdclass
    {
        switch ($viewReference) {
            case self::VIEW_NAMES['taskList']:
                $this->viewModel = $this->prepareTaskListData();
                break;
            case self::VIEW_NAMES['createTask']:
                $this->viewModel = $this->prepareCreateTaskData();
                break;
            case self::VIEW_NAMES['editTask']:
                $this->viewModel = $this->prepareEditTaskData();
                break;
            case self::VIEW_NAMES['toggleTask']:
                $this->viewModel = $this->prepareToggleTaskData();
                break;
            case self::VIEW_NAMES['deleteTask']:
                $this->viewModel = $this->prepareDeleteTaskData();
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
        $length = count($tasks);
        $formNamePrefix = self::VIEW_NAMES['toggleTask'] . '_';
        // Current (submitted) task toggle form name does not contain an integer as suffix!
        if (null !== $currentForm && !preg_match('/(\d+)$/', $currentForm->getName(), $matches)) {
            throw new \RuntimeException("Current form name suffix is expected to be an integer as index!");
        }
        for ($i = 0; $i < $length; $i++) {
            $taskId = $tasks[$i]->getId();
            // Create current (submitted) form view if not null with its actual state!
            if (isset($matches) && $i === ($matches[1] - 1)) {
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
     * @return \StdClass
     */
    private function prepareTaskListData(): \StdClass
    {
        // IMPORTANT: optimize query for performance later!
        $tasks = $this->entityManager->getRepository(Task::class)->findAll();
        $this->viewModel->tasks = $tasks;
        $this->viewModel->toggleTaskFormViews = $this->generateToggleTaskFormViews(
            $tasks,
            $this->viewModel->form ?? null
        );
        // IMPORTANT: add delete form views later here!
        return $this->viewModel;
    }

    /**
     * Prepare "create task" particular view data.
     *
     * @return \StdClass
     */
    private function prepareCreateTaskData(): \StdClass
    {
        $this->viewModel->createTaskFormView = $this->viewModel->form->createView();
        // Delete unnecessary property
        unset($this->viewModel->{'form'});

        return $this->viewModel;
    }

    /**
     * Prepare "edit task" particular view data.
     *
     * @return \StdClass
     */
    private function prepareEditTaskData(): \StdClass
    {
        $this->viewModel->editTaskFormView = $this->viewModel->form->createView();
        $this->viewModel->taskId = $this->viewModel->task->getId();
        // Delete unnecessary properties
        unset($this->viewModel->{'form'});
        unset($this->viewModel->{'task'});

        return $this->viewModel;
    }

    /**
     * Prepare "toggle task" particular view data.
     *
     * @return \StdClass
     */
    private function prepareToggleTaskData(): \StdClass
    {
        return $this->prepareTaskListData();
    }

    /**
     * Prepare "delete task" particular view data.
     *
     * @return \StdClass
     */
    private function prepareDeleteTaskData(): \StdClass
    {
        // IMPORTANT: change this method script here later if necessary!
        return $this->prepareTaskListData();
    }
}