<?php

declare(strict_types=1);

namespace App\Tests\Security\Authorization;

use App\Entity\Task;
use App\Entity\User;
use App\Security\Authorization\TaskVoter;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;

/**
 * Class TaskVoterTest.
 *
 * Manage unit tests for Task voter.
 */
class TaskVoterTest extends TestCase
{
    /**
     * @var TaskVoter|null
     */
    private ?TaskVoter $voter;

    /**
     * @var MockObject|Security|null
     */
    private ?Security $security;

    /**
     * Setup one trick voter instance.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->security = $this->createPartialMock(Security::class, ['isGranted']);
        $this->voter = new TaskVoter($this->security);
    }

    /**
     * Mock a User entity with admin role.
     *
     * @return User
     */
    private function createAdmin(): User
    {
        $user1 = $this->createPartialMock(User::class, ['getId', 'getRoles']);
        $user1->method('getId')->willReturn(1);
        $user1->method('getRoles')->willReturn(['ROLE_ADMIN', 'ROLE_USER']);

        return $user1;
    }

    /**
     * Get a set of default Task entities without author.
     *
     * @return array|Task[]
     */
    private function createDefaultTasks(): array
    {
        $task1 = $this->createPartialMock(Task::class, ['getId', 'getAuthor']);
        $task1->method('getId')->willReturn(1);
        $task2 =  $this->createPartialMock(Task::class, ['getId', 'getAuthor']);
        $task2->method('getId')->willReturn(2);
        $task3 =  $this->createPartialMock(Task::class, ['getId', 'getAuthor']);
        $task3->method('getId')->willReturn(3);

        return [$task1, $task2, $task3];
    }

    /**
     * Mock a set of User entities with simple user role who will be considered as authenticated.
     *
     * @return array|User[]
     */
    private function createUsers(): array
    {
        $user2 = $this->createPartialMock(User::class, ['getId', 'getRoles']);
        $user2->method('getId')->willReturn(2);
        $user2->method('getRoles')->willReturn(['ROLE_USER']);
        $user3 = $this->createPartialMock(User::class, ['getId', 'getRoles']);
        $user3->method('getId')->willReturn(3);
        $user3->method('getRoles')->willReturn(['ROLE_USER']);

        return [$user2, $user3];
    }

    /**
     * Get user token depending on authentication.
     *
     * @param User|null $user
     *
     * @return TokenInterface
     */
    private function createUserToken(?User $user): TokenInterface
    {
        $token = new AnonymousToken('secret', 'anonymous');
        if ($user) {
            $token = new UsernamePasswordToken(
                $user, 'credentials', 'memory', $user->getRoles()
            );
        }

        return $token;
    }

    /**
     * Provide anonymous user task deletion authorization cases data
     * to test denied access.
     *
     * @return \Generator
     */
    public function getAnonymousUserTaskDeletionAuthorizationCasesDataProvider(): \Generator
    {
        // Get a simple authenticated user
        $authenticatedUser1 = $this->createUsers()[0];
        // Get tasks to test
        $tasks = $this->createDefaultTasks();
        $tasks[0]->method('getAuthor')->willReturn($authenticatedUser1);
        $taskWithUser1AsAuthor = $tasks[0];
        $taskWithoutAuthor = $tasks[1];
        // An anonymous user is never allowed to delete a task,
        // but check user particular permission!
        yield 'Anonymous user cannot delete a task by checking user particular permission' => [
            TaskVoter::USER_CAN_DELETE_IT_AS_AUTHOR,
            $taskWithUser1AsAuthor,
            null,
            Voter::ACCESS_DENIED
        ];
        // An anonymous user is never allowed to delete a task,
        // but check admin particular permission (with no author set)!
        yield 'Anonymous user cannot delete a task by checking admin particular permission' => [
            TaskVoter::ADMIN_CAN_DELETE_IT_WITHOUT_AUTHOR,
            $taskWithoutAuthor,
            null,
            Voter::ACCESS_DENIED
        ];
    }

    /**
     * Provide authenticated simple user task deletion authorization cases data
     * to test denied or granted access.
     *
     * @return \Generator
     */
    public function getAuthenticatedUserTaskDeletionAuthorizationCasesDataProvider(): \Generator
    {
        // Get simple authenticated users
        $authenticatedUser1 = $this->createUsers()[0];
        $authenticatedUser2 = $this->createUsers()[1];
        // Get tasks to test
        $tasks = $this->createDefaultTasks();
        $tasks[0]->method('getAuthor')->willReturn($authenticatedUser1);
        $tasks[2]->method('getAuthor')->willReturn($authenticatedUser2);
        $taskWithUser1AsAuthor = $tasks[0];
        $taskWithoutAuthor = $tasks[1];
        $taskWithUser2AsAuthor = $tasks[2];
        // A simple member is not allowed to delete a task if he is not the author.
        yield 'Simple authenticated user cannot delete a task without being the author' => [
            TaskVoter::USER_CAN_DELETE_IT_AS_AUTHOR,
            $taskWithUser2AsAuthor,
            $authenticatedUser1,
            Voter::ACCESS_DENIED
        ];
        // A simple member is not allowed to delete a task if no author is set.
        yield 'Simple authenticated user cannot delete a task without author' => [
            TaskVoter::ADMIN_CAN_DELETE_IT_WITHOUT_AUTHOR,
            $taskWithoutAuthor, // Author is set to "null" by default.
            $authenticatedUser1,
            Voter::ACCESS_DENIED
        ];
        // A simple member is allowed to delete a task if he is the author.
        yield 'Simple authenticated user can delete a task as author' => [
            TaskVoter::USER_CAN_DELETE_IT_AS_AUTHOR,
            $taskWithUser1AsAuthor,
            $authenticatedUser1,
            Voter::ACCESS_GRANTED
        ];
    }

    /**
     * Provide authenticated administrator user task deletion authorization cases data
     * to test denied or granted access.
     *
     * @return \Generator
     */
    public function getAuthenticatedAdminTaskDeletionAuthorizationCasesDataProvider(): \Generator
    {
        // Get a simple authenticated user
        $authenticatedUser1 = $this->createUsers()[0];
        // Get an authenticated admin user
        $authenticatedAdmin = $this->createAdmin();
        // Get tasks to test
        $tasks = $this->createDefaultTasks();
        $tasks[0]->method('getAuthor')->willReturn($authenticatedUser1);
        $tasks[2]->method('getAuthor')->willReturn($authenticatedAdmin);
        $taskWithUser1AsAuthor = $tasks[0];
        $taskWithoutAuthor = $tasks[1];
        $taskWithAdminAsAuthor = $tasks[2];
        // An administrator is not allowed to delete a task if he is not the author.
        yield 'Admin authenticated user cannot delete a task without being the author' => [
            TaskVoter::USER_CAN_DELETE_IT_AS_AUTHOR,
            $taskWithUser1AsAuthor,
            $authenticatedAdmin,
            Voter::ACCESS_DENIED
        ];
        // An administrator is allowed to delete a task if he is the author.
        yield 'Admin authenticated user can delete a task as author' => [
            TaskVoter::USER_CAN_DELETE_IT_AS_AUTHOR,
            $taskWithAdminAsAuthor,
            $authenticatedAdmin,
            Voter::ACCESS_GRANTED
        ];
        // An administrator is allowed to delete a task if no author is set.
        yield 'Admin authenticated user can delete a task without author' => [
            TaskVoter::ADMIN_CAN_DELETE_IT_WITHOUT_AUTHOR,
            $taskWithoutAuthor, // Author is set to "null" by default.
            $authenticatedAdmin,
            Voter::ACCESS_GRANTED
        ];
    }

    /**
     * Test if an anonymous user can delete a task.
     *
     * @dataProvider getAnonymousUserTaskDeletionAuthorizationCasesDataProvider
     *
     * @param string    $attribute
     * @param Task      $task
     * @param User|null $user
     * @param int       $expectedVote
     *
     * @return void
     */
    public function testVoteIfAnonymousCanDeleteTask(
        string $attribute,
        Task $task,
        ?User $user,
        $expectedVote
    ): void {
        // Define denied access without admin role
        $this->security
            ->method('isGranted')
            ->willReturn(false);
        // Get TaskVoter instance
        $voter = $this->voter;
        // Get user token (anonymous or authenticated one)
        $token = $this->createUserToken($user);
        $this->assertSame(
            $expectedVote,
            $voter->vote($token, $task, [$attribute])
        );
    }

    /**
     * Test if an authenticated user can delete a task.
     *
     * @dataProvider getAuthenticatedUserTaskDeletionAuthorizationCasesDataProvider
     *
     * @param string    $attribute
     * @param Task      $task
     * @param User|null $user
     * @param int       $expectedVote
     *
     * @return void
     */
    public function testVoteIfAuthenticatedMemberCanDeleteTask(
        string $attribute,
        Task $task,
        ?User $user,
        $expectedVote
    ): void {
        // Define denied access without admin role
        $this->security
            ->method('isGranted')
            ->willReturn(false);
        // Get TaskVoter instance
        $voter = $this->voter;
        // Get user token (anonymous or authenticated one)
        $token = $this->createUserToken($user);
        $this->assertSame(
            $expectedVote,
            $voter->vote($token, $task, [$attribute])
        );
    }

    /**
     * Test if an authenticated administrator can delete a task.
     *
     * @dataProvider getAuthenticatedAdminTaskDeletionAuthorizationCasesDataProvider
     *
     * @param string    $attribute
     * @param Task      $task
     * @param User|null $user
     * @param int       $expectedVote
     *
     * @return void
     */
    public function testVoteIfAuthenticatedAdminCanDeleteTask(
        string $attribute,
        Task $task,
        ?User $user,
        $expectedVote
    ): void {
        // Define granted access with admin role
        $this->security
            ->method('isGranted')
            ->willReturn(true);
        // Get TaskVoter instance
        $voter = $this->voter;
        // Get user token (anonymous or authenticated one)
        $token = $this->createUserToken($user);
        $this->assertSame(
            $expectedVote,
            $voter->vote($token, $task, [$attribute])
        );
    }

    /**
     * Test if a wrong role attribute is passed to TaskVoter::voteOnAttribute().
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testVoteWithWrongAttribute(): void
    {
        // Get task to test
        $task = new Task();
        // Get user (can be authenticated or anonymous user, it doesn't matter)
        $user = new User();
        // Get anonymous user token (it is sufficient to test exception.)
        $token = $this->createUserToken($user);
        // Get TaskVoter instance
        $voter = $this->voter;
        $this->assertSame(
            Voter::ACCESS_ABSTAIN,
            $voter->vote($token, $task, ['WRONG_ROLE_ATTRIBUTE'])
        );
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->security = null;
        $this->voter = null;
    }
}
