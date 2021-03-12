<?php

declare(strict_types=1);

namespace App\Entity\Manager;

use App\Entity\Task;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class TaskManager
 *
 * Manage Task entity operations as a service layer.
 */
class TaskManager extends AbstractModelManager
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
        try {
            $this->getPersistenceLayer()->persist($newTask);
            $this->getPersistenceLayer()->flush();

            return true;
        } catch (\Exception $exception) {
            $this->logger->error('Task persistence error:' . $exception->getMessage());

            return false;
        }
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
        try {
            $this->getPersistenceLayer()->flush();

            return true;
        } catch (\Exception $exception) {
            $this->logger->error('Task update error:' . $exception->getMessage());

            return false;
        }
    }
}