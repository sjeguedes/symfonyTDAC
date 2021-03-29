<?php

declare(strict_types=1);

namespace App\Entity\Manager;

use App\Entity\User;

/**
 * Class UserDataModelManager
 *
 * Manage User entity operations as a service layer.
 */
class UserDataModelManager extends AbstractDataModelManager
{
    /**
     * Add a new user.
     *
     * @param User $newUser
     *
     * @return bool
     */
    public function create(User $newUser): bool
    {
        // Save the new user
        return $this->save($newUser, 'User persistence error', true);
    }

    /**
     * Update an existing user.
     *
     * @param User $user
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function update(User $user): bool
    {
        // Trace user update
        $user->setUpdatedAt(new \DateTimeImmutable());
        // Save the change(s) made on user
        return $this->save($user, 'User update error');
    }

    /**
     * Delete an existing user.
     *
     * @param User $user
     *
     * @return bool
     */
    public function delete(User $user): bool
    {
        // Remove user and save deletion
        return $this->remove($user, 'User removal error');
    }
}