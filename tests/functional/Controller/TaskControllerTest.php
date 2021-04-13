<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Task;
use App\Tests\Functional\Controller\Helpers\AbstractControllerWebTestCase;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Class TaskControllerTest
 *
 * Define functional tests for TaskController.
 */
class TaskControllerTest extends AbstractControllerWebTestCase
{
    /**
     * Define form fields base names.
     */
    private const BASE_FORM_FIELDS_NAMES = [
        'task_creation' => 'create_task[task]', // compound form
        'task_update'   => 'edit_task[task]', // compound form
        'task_toggle'   => 'toggle_task',
        'task_deletion' => 'delete_task'
    ];

    /**
     * Provide controller methods URIs.
     *
     * @return array
     */
    public function provideURIs(): array
    {
        return [
            'List tasks'           => ['GET', '/tasks'],
            'Access task creation' => ['GET', '/tasks/create'],
            'Create a task'        => ['POST', '/tasks/create'],
            'Access task update'   => ['GET', '/tasks/1/edit'],
            'Update (Edit) a task' => ['POST', '/tasks/1/edit'],
            'Toggle a task state'  => ['PATCH', '/tasks/1/toggle'],
            'Delete a task state'  => ['DELETE', '/tasks/1/delete']
        ];
    }

    /**
     * Provide controller methods forms data.
     *
     * @return \Generator
     */
    public function provideFormsConfigurations(): \Generator
    {
        yield 'Gets form data to create a task' => [
            'data' => [
                'uri'              => '/tasks/create',
                'form_name'        => self::BASE_FORM_FIELDS_NAMES['task_creation'],
                'csrf_token_id'    => 'create_task_action',
                'submit_button_id' => 'create-task'
            ]
        ];
        yield 'Gets form data to edit a task' => [
            'data' => [
                'uri'              => '/tasks/1/edit',
                'form_name'        => self::BASE_FORM_FIELDS_NAMES['task_update'],
                'csrf_token_id'    => 'edit_task_action',
                'submit_button_id' => 'edit-task'
            ]
        ];
        yield 'Gets form data to toggle a task' => [
            'data' => [
                'uri'              => '/tasks',
                'form_name'        => self::BASE_FORM_FIELDS_NAMES['task_toggle'] . '_1',
                'csrf_token_id'    => 'toggle_task_action',
                'submit_button_id' => 'toggle-task-1'
            ]
        ];
        yield 'Gets form data to delete a task' => [
            'data' => [
                'uri'              => '/tasks',
                'form_name'        => self::BASE_FORM_FIELDS_NAMES['task_deletion']. '_1',
                'csrf_token_id'    => 'delete_task_action',
                'submit_button_id' => 'delete-task-1'
            ]
        ];
        // Add other forms here
    }

    /**
     * Check that a user should authenticate to be able to perform task actions.
     *
     * @dataProvider provideURIs
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    public function testUnauthenticatedUserCannotAccessTaskRequests(string $method, string $uri): void
    {
        $this->client->request($method, $uri);
        // Use assertions with custom method
        static::assertAccessIsUnauthorizedWithTemporaryRedirection($this->client);
    }

    /**
     * Check that forms CSRF protection is correctly set.
     *
     * @dataProvider provideFormsConfigurations
     *
     * @param array $data
     *
     * @return void
     */
    public function testCsrfProtectionIsActiveAndCorrectlyConfigured(array $data): void
    {
        $this->loginUser();
        $crawler = $this->client->request('GET', $data['uri']);
        /** @var CsrfToken $csrfToken */
        $csrfToken = static::$container->get('security.csrf.token_manager')->getToken($data['csrf_token_id']);
        // Csrf token form is outside nested "task" form type!
        $csrfTokenFieldNameAttribute = str_replace('[task]', '', $data['form_name']) . '[_token]';
        $buttonCrawlerNode = $crawler->selectButton($data['submit_button_id']);
        $form = $buttonCrawlerNode->form();
        // Check that CSRF token value is present among form values
        static::assertTrue(\in_array($csrfToken->getValue(), $form->getValues()));
        // Get consistency by keeping the same CSRF token name for all forms
        static::assertTrue($form->has($csrfTokenFieldNameAttribute));
        // Pass a wrong token
        $form[$csrfTokenFieldNameAttribute] = 'Wrong CSRF token';
        $crawler = $this->client->submit($form);
         // Check that CSRF token cannot be tampered!
        static::assertCount(1, $crawler->filter('div.alert-danger'));
    }

    /**
     * Check that tasks can be correctly listed.
     *
     * @return void
     */
    public function testTasksCanBeListed(): void
    {
        $this->loginUser();
        // Access full task list
        $crawler = $this->client->request('GET', '/tasks');
        static::assertSame(20, $crawler->filter('div.task')->count());
        // Click on filter buttons to check CTA existence and list results and go back to full list
        $crawler = $this->client->clickLink('Consulter les tâches à faire');
        static::assertSame(10, $crawler->filter('div.task')->count());
        $crawler = $this->client->clickLink('Consulter la liste des tâches');
        static::assertSame(20, $crawler->filter('div.task')->count());
        $crawler = $this->client->clickLink('Consulter les tâches terminées');
        static::assertSame(10, $crawler->filter('div.task')->count());
    }

    /**
     * Check that a new task is correctly created.
     *
     * @return void
     */
    public function testNewTaskCanBeCreated(): void
    {
        $this->loginUser();
        $this->client->request('GET', '/tasks/create');
        $this->client->submitForm('Ajouter', [
            self::BASE_FORM_FIELDS_NAMES['task_creation'] . '[title]'   => 'Nouvelle tâche',
            self::BASE_FORM_FIELDS_NAMES['task_creation'] . '[content]' => 'Ceci est un contenu de nouvelle tâche.'
        ], 'POST');
        static::assertTrue($this->client->getResponse()->isRedirect('/tasks'));
        $crawler = $this->client->followRedirect();
        static::assertSame(
            'Superbe ! La tâche a bien été ajoutée.',
            trim($crawler->filter('div.alert-success')->text(null, false))
        );
    }

    /**
     * Check that a new task is correctly associated to authenticated user.
     *
     * @return void
     */
    public function testNewTaskIsAssociatedToAuthenticatedUser(): void
    {
        $testUser = $this->loginUser();
        $this->client->request('GET', '/tasks/create');
        $uniqueID = time();
        $this->client->submitForm('Ajouter', [
            self::BASE_FORM_FIELDS_NAMES['task_creation'] . '[title]' => 'Nouvelle tâche ' . $uniqueID,
            self::BASE_FORM_FIELDS_NAMES['task_creation'] . '[content]' => 'Ceci est un contenu de nouvelle tâche.'
        ], 'POST');
        /** @var ObjectRepository $taskRepository */
        $taskRepository = static::$container->get('doctrine')->getRepository(Task::class);
        $newTask = $taskRepository->findOneBy(['title' => 'Nouvelle tâche ' . $uniqueID]);
        // Check that authenticated user is set as the new task author
        static::assertEquals($testUser->getId(), $newTask->getAuthor()->getId());
    }

    /**
     * Check that an existing task is correctly updated.
     *
     * @return void
     */
    public function testExistingTaskCanBeUpdated(): void
    {
        $this->loginUser();
        // Get one of the 20 existing test tasks
        $randomId = rand(1, 20);
        $this->client->request('GET', '/tasks/' . $randomId . '/edit');
        $this->client->submitForm('Modifier', [
            self::BASE_FORM_FIELDS_NAMES['task_update'] . '[title]'   => 'Tâche modifiée',
            self::BASE_FORM_FIELDS_NAMES['task_update'] . '[content]' => 'Ceci est un changement de contenu de la tâche.'
        ], 'POST');
        static::assertTrue($this->client->getResponse()->isRedirect('/tasks'));
        $crawler = $this->client->followRedirect();
        static::assertSame(
            'Superbe ! La tâche a bien été modifiée.',
            trim($crawler->filter('div.alert-success')->text(null, false))
        );
    }

    /**
     * Check that an existing task is correctly toggled (marked as done or not).
     *
     * @return void
     */
    public function testExistingTaskCanBeToggled(): void
    {
        $this->loginUser();
        $crawler = $this->client->request('GET', '/tasks');
        /** @var ObjectRepository $taskRepository */
        $taskRepository = static::$container->get('doctrine')->getRepository(Task::class);
        // Get one of the 20 existing test tasks
        $randomId = rand(1, 20);
        $existingTask = $taskRepository->find($randomId);
        // Get task "isDone" state before toggle
        $previousIsDoneValue = $existingTask->isDone();
        // No data is submitted during toggle action, only the task id is taken into account!
        $form = $crawler->selectButton('toggle-task-' . $randomId)->form();
        $this->client->submit($form);
        $toggledTask = $taskRepository->find($randomId);
        // Check that isDone" state is inverse after toggle
        static::assertSame(!$previousIsDoneValue, $toggledTask->isDone());
    }

    /**
     * Check that an existing task is correctly deleted.
     *
     * @return void
     */
    public function testExistingTaskCanBeDeleted(): void
    {
        $this->loginUser();
        $crawler = $this->client->request('GET', '/tasks');
        /** @var ObjectRepository $taskRepository */
        $taskRepository = static::$container->get('doctrine')->getRepository(Task::class);
        // Get one of the 20 existing test tasks
        $randomId = rand(1, 20);
        $existingTask = $taskRepository->find($randomId);
        static::assertInstanceOf(Task::class, $existingTask);
        // No data is submitted during toggle action, only the task id is taken into account!
        $form = $crawler->selectButton('delete-task-' . $randomId)->form();
        $this->client->submit($form);
        $deletedTask = $taskRepository->find($randomId);
        // Check that task with id "$randomId" does not exist anymore
        static::assertSame(null, $deletedTask);
    }

    /**
     * Check that "toggle" action reverses texts status correctly depending on "isDone" state.
     *
     * @return void
     */
    public function testExistingTaskWasToggledWithCorrectTextsStatus(): void
    {
        $this->loginUser();
        $crawler = $this->client->request('GET', '/tasks');
        /** @var ObjectRepository $taskRepository */
        $taskRepository = static::$container->get('doctrine')->getRepository(Task::class);
        // Get one of the 20 existing test tasks
        $randomId = rand(1, 20);
        $existingTask = $taskRepository->find($randomId);
        // No data is submitted during toggle action, only the task id is taken into account!
        $form = $crawler->selectButton('toggle-task-' . $randomId)->form();
        $this->client->submit($form);
        static::assertTrue($this->client->getResponse()->isRedirect('/tasks'));
        $crawler = $this->client->followRedirect();
        $toggledTask = $taskRepository->find($randomId);
        // Check flash success message content
        static::assertSame(
            sprintf(
                'Superbe ! La tâche "%s" a bien été marquée comme %s.',
                $existingTask->getTitle(),
                $toggledTask->isDone() ? 'faite' : 'non terminée'
            ),
            trim($crawler->filter('div.alert-success')->text(null, false))
        );
        // Check corresponding toggle button correct label
        static::assertSame(
            'Marquer comme ' . ($toggledTask->isDone() ? 'non terminée' : 'faite'),
            trim($crawler->filter('#toggle-task-' . $randomId)->text(null, false))
        );
    }

    /**
     * Check that an existing task cannot be updated without form inputs modification (compared to initial data).
     *
     * @return void
     */
    public function testExistingTaskCannotBeUpdatedWithoutFormInputsChanges(): void
    {
        $this->loginUser();
        // Get one of the 20 existing test tasks
        $randomId = rand(1, 20);
        $this->client->request('GET', '/tasks/' . $randomId . '/edit');
        /** @var ObjectRepository $taskRepository */
        $taskRepository = static::$container->get('doctrine')->getRepository(Task::class);
        $existingTask = $taskRepository->find($randomId);
        $crawler =$this->client->submitForm('Modifier', [
            self::BASE_FORM_FIELDS_NAMES['task_update'] . '[title]'   => $existingTask->getTitle(),
            self::BASE_FORM_FIELDS_NAMES['task_update'] . '[content]' => $existingTask->getContent()
        ], 'POST');
        static::assertFalse($this->client->getResponse()->isRedirect('/tasks'));
        static::assertSame(
            'Surprenant ! Aucun changement n\'a été effectué.',
            trim($crawler->filter('div.alert-warning')->text(null, false))
        );
    }

    /**
     * Check that an existing task is correctly associated to authenticated user as last editor.
     *
     * Please note that task author is not modified as expected.
     *
     * @return void
     */
    public function testExistingTaskWasUpdatedByAuthenticatedUserAsLastEditor(): void
    {
        $testUser = $this->loginUser();
        // Get one of the 20 existing test tasks
        $randomId = rand(1, 20);
        $this->client->request('GET', '/tasks/' . $randomId . '/edit');
        $this->client->submitForm('Modifier', [
            self::BASE_FORM_FIELDS_NAMES['task_update'] . '[title]'   => 'Tâche modifiée',
            self::BASE_FORM_FIELDS_NAMES['task_update'] . '[content]' => 'Ceci est un changement de contenu de la tâche.'
        ], 'POST');
        /** @var ObjectRepository $taskRepository */
        $taskRepository = static::$container->get('doctrine')->getRepository(Task::class);
        $existingTask = $taskRepository->find($randomId);
        // Check that author remained unchanged after update ("null" since defined without author by default)
        static::assertSame(null, $existingTask->getAuthor());
        // Check that authenticated user is set as the last editor
        static::assertEquals($testUser->getId(), $existingTask->getLastEditor()->getId());
    }

    /**
     * Check that technical error (database operations failure) is taken into account
     * to improve UX during task creation.
     *
     * @return void
     */
    public function testTechnicalErrorIsTakenIntoAccountOnTaskCreationORMFailure(): void
    {
        $this->loginUser();
        // Call the request
        $this->client->request('GET', '/tasks/create');
        foreach ([Events::postPersist, Events::onFlush] as $eventName) {
            // Simulate ORM exception thanks to particular test Doctrine listener
            $this->makeEntityManagerThrowExceptionOnORMOperations($eventName);
            // Submit the form
            $crawler = $this->client->submitForm('Ajouter', [
                self::BASE_FORM_FIELDS_NAMES['task_creation'] . '[title]'   => 'Nouvelle tâche',
                self::BASE_FORM_FIELDS_NAMES['task_creation'] . '[content]' => 'Ceci est un contenu de nouvelle tâche.'
            ], 'POST');
            // Check that no redirection is made to task list
            static::assertFalse($this->client->getResponse()->isRedirect('/tasks'));
            // Ensure correct UX is displayed when ORM operation on entity failed
            static::assertSame(
                'Oops ! Un problème est survenu !',
                trim($crawler->filter('div.alert-danger')->text(null, false))
            );
        }
    }

    /**
     * Check that technical error (database operations failure) is taken into account
     * to improve UX during task update.
     *
     * @return void
     */
    public function testTechnicalErrorIsTakenIntoAccountOnTaskUpdateORMFailure(): void
    {
        $this->loginUser();
        // Get one of the 20 existing test tasks
        $randomId = rand(1, 20);
        // Call the request
        $this->client->request('GET', '/tasks/' . $randomId . '/edit');
        foreach ([Events::postUpdate, Events::onFlush] as $eventName) {
            // Simulate ORM exception thanks to particular test Doctrine listener
            $this->makeEntityManagerThrowExceptionOnORMOperations($eventName);
            // Submit the form
            $crawler = $this->client->submitForm('Modifier', [
                self::BASE_FORM_FIELDS_NAMES['task_update'] . '[title]'   => 'Tâche modifiée',
                self::BASE_FORM_FIELDS_NAMES['task_update'] . '[content]' => 'Ceci est un changement de contenu de la tâche.'
            ], 'POST');
            // Check that no redirection is made to task list
            static::assertFalse($this->client->getResponse()->isRedirect('/tasks'));
            // Ensure correct UX is displayed when ORM operation on entity failed
            static::assertSame(
                'Oops ! Un problème est survenu !',
                trim($crawler->filter('div.alert-danger')->text(null, false))
            );
        }
    }

    /**
     * Check that technical error (database operations failure) is taken into account
     * to improve UX during task toggle.
     *
     * @return void
     */
    public function testTechnicalErrorIsTakenIntoAccountOnTaskToggleORMFailure(): void
    {
        $this->loginUser();
        // Get one of the 20 existing test tasks
        $randomId = rand(1, 20);
        // Call the request
        $crawler = $this->client->request('GET', '/tasks');
        foreach ([Events::postUpdate, Events::onFlush] as $eventName) {
            // Simulate ORM exception thanks to particular test Doctrine listener
            $this->makeEntityManagerThrowExceptionOnORMOperations($eventName);
            // No data is submitted during toggle action, only the task id is taken into account!
            $form = $crawler->selectButton('toggle-task-' . $randomId)->form();
            // Submit the form
            $crawler = $this->client->submit($form);
            // Check that no redirection is made to task list
            static::assertFalse($this->client->getResponse()->isRedirect('/tasks'));
            // Ensure correct UX is displayed when ORM operation on entity failed
            static::assertSame(
                'Oops ! Un problème est survenu !',
                trim($crawler->filter('div.alert-danger')->text(null, false))
            );
        }
    }

    /**
     * Check that technical error (database operations failure) is taken into account
     * to improve UX during task deletion.
     *
     * @return void
     */
    public function testTechnicalErrorIsTakenIntoAccountOnTaskDeletionORMFailure(): void
    {
        $this->loginUser();
        // Get one of the 20 existing test tasks
        $randomId = rand(1, 20);
        // Call the request
        $crawler = $this->client->request('GET', '/tasks');
        foreach ([Events::postRemove, Events::onFlush] as $eventName) {
            // Simulate ORM exception thanks to particular test Doctrine listener
            $this->makeEntityManagerThrowExceptionOnORMOperations($eventName);
            // No data is submitted during deletion action, only the task id is taken into account!
            $form = $crawler->selectButton('delete-task-' . $randomId)->form();
            // Submit the form
            $crawler = $this->client->submit($form);
            // Check that no redirection is made to task list
            static::assertFalse($this->client->getResponse()->isRedirect('/tasks'));
            // Ensure correct UX is displayed when ORM operation on entity failed
            static::assertSame(
                'Oops ! Un problème est survenu !',
                trim($crawler->filter('div.alert-danger')->text(null, false))
            );
        }
    }
}