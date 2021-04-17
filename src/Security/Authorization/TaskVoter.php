<?php

declare(strict_types=1);

namespace App\Security\Authorization;

use App\Entity\Task;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

/**
 * Class TaskVoter
 *
 * Manage particular permissions for task actions.
 */
class TaskVoter extends Voter
{
    /**
     * Define an simple authenticated user permission to be able to delete one of his own tasks
     * (among those he created).
     */
    const USER_CAN_DELETE_IT_AS_AUTHOR = 'USER_CAN_DELETE_IT_AS_AUTHOR';

    /**
     * Define an admin permission to be able to delete a task
     * without author (considered as "anonymous") set to "null" in database.
     */
    const ADMIN_CAN_DELETE_IT_WITHOUT_AUTHOR = 'ADMIN_CAN_DELETE_IT_WITHOUT_AUTHOR';

    /**
     * @var Security
     */
    private Security $security;

    /**
     * TaskVoter constructor.
     *
     * @param Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Is this Voter able to vote on permission.
     *
     * {@inheritdoc}
     */
    protected function supports($attribute, $subject): bool
    {
        // If the attribute isn't one we support, return false!
        if (!\in_array($attribute, [self::USER_CAN_DELETE_IT_AS_AUTHOR, self::ADMIN_CAN_DELETE_IT_WITHOUT_AUTHOR])) {
            return false;
        }
        // Only vote on Task instance
        if (!$subject instanceof Task) {
            return false;
        }

        return true;
    }

    /**
     * Vote on permission to check.
     *
     * {@inheritdoc}
     */
    protected function voteOnAttribute($attribute, $subject, TokenInterface $token): bool
    {
        // Get authenticated user
        $user = $token->getUser();
        // Current user must be logged in, otherwise deny access!
        if (!$user instanceof User) {
            return false;
        }
        // Get involved task to vote on
        /** @var Task $task */
        $task = $subject;
        // Check permission
        switch ($attribute) {
            case self::USER_CAN_DELETE_IT_AS_AUTHOR:
                return $this->isUserAllowedToDeleteAsAuthor($task, $user);
            case self::ADMIN_CAN_DELETE_IT_WITHOUT_AUTHOR:
                return $this->isAdminAllowedToDeleteWithoutAuthor($task);
            default:
                // @codeCoverageIgnoreStart
                throw new \LogicException(
                    'This code should not be reached! Task Voter checked permission is unknown.'
                );
                // @codeCoverageIgnoreEnd
        }
    }

    /**
     * Check that current task has an author, and authenticated user created it
     * in order to allow to delete this task.
     *
     * @param Task $task
     * @param User $user
     *
     * @return bool
     */
    private function isUserAllowedToDeleteAsAuthor(Task $task, User $user): bool
    {
        // If task has no author (considered as "anonymous" author), no permission is allowed in this case!
        if (null === $task->getAuthor()) {
            return false;
        }
        // Is authenticated user corresponding task author?
        return $task->getAuthor()->getId() === $user->getId();
    }

    /**
     * Check that current task has no author, and authenticated user has admin role
     * in order to allow to delete this task.
     *
     * @param Task $task
     *
     * @return bool
     */
    private function isAdminAllowedToDeleteWithoutAuthor(Task $task): bool
    {
        // Check that task has no author (considered as "anonymous" author), otherwise permission is not allowed!
        if (null !== $task->getAuthor()) {
            return false;
        }
        // Check that authenticated user has "admin" role, otherwise permission is not allowed!
        if (!$this->security->isGranted(explode(',', User::ROLES['admin']))) {
            return false;
        }

        return true;
    }
}
