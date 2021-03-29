<?php

declare(strict_types=1);

namespace App\Entity\Manager;

use App\Entity\Task;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class TaskDataModelManager
 *
 * Manage Task entity operations as a service layer.
 */
class TaskDataModelManager extends AbstractDataModelManager
{
    /**
     * Add a new task associated to authenticated user as author.
     *
     * @param Task          $newTask
     * @param UserInterface $author
     *
     * @return bool
     */
    public function create(Task $newTask, UserInterface $author): bool
    {
        // Associate authenticated user as author (which can be only set here)
        $newTask->setAuthor($author);
        // Save the new task
        return $this->save($newTask, 'Task persistence error', true);
    }

    /**
     * Update an existing task associated to authenticated user as last editor.
     *
     * @param Task $task
     * @param UserInterface $lastEditor
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function update(Task $task, UserInterface $lastEditor): bool
    {
        // Associate authenticated user as last editor (Author is locked with exception thrown in setter!)
        $task->setLastEditor($lastEditor);
        // Trace task update
        $task->setUpdatedAt(new \DateTimeImmutable());
        // Save the change(s) made on task
        return $this->save($task, 'Task update error');
    }

    /**
     * Change an existing task "isDone" state by toggling value.
     *
     * @param Task $task
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function toggle(Task $task): bool
    {
        // Inverse "isDone" value internally
        $task->toggle();
        // Trace task update
        $task->setUpdatedAt(new \DateTimeImmutable());
        // Save the change(s) made on task
        return $this->save($task, 'Task toggle error');
    }

    /**
     * Delete an existing task.
     *
     * @param Task $task
     *
     * @return bool
     */
    public function delete(Task $task): bool
    {
        // Remove task and save deletion
        return $this->remove($task, 'Task removal error');
    }
}