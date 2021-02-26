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
     * Add a new task associated to authenticated user.
     *
     * @param Task          $newTask
     * @param UserInterface $authenticatedUser
     *
     * @return void
     */
    public function create(Task $newTask, UserInterface $authenticatedUser): void
    {
        $newTask->setAuthor($authenticatedUser);
        // Save the new task
        $this->saveData($newTask);
    }
}