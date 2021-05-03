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
     * @param User   $newUser
     * @param string $encodedPassword
     *
     * @return bool
     */
    public function create(User $newUser, string $encodedPassword): bool
    {
        // Use password with encoded string version
        $newUser->setPassword($encodedPassword);
        // Save the new user
        return $this->save($newUser, 'User persistence error', true);
    }

    /**
     * Update an existing user.
     *
     * @param User   $user
     * @param string $encodedPassword
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function update(User $user, string $encodedPassword): bool
    {
        // Update password with encoded string version
        $user->setPassword($encodedPassword);
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
