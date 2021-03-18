<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Task;
use App\Tests\Functional\Controller\Helpers\AbstractControllerTestCase;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Class TaskControllerTest
 *
 * Define functional tests for TaskController.
 */
class TaskControllerTest extends AbstractControllerTestCase
{
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
            'Toggle a task state'  => ['POST', '/tasks/1/toggle'],
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
        yield 'Form data to create a task' => [
            'data' => [
                'uri'              => '/tasks/create',
                'form_name'        => 'create_task',
                'csrf_token_id'    => 'create_task_action',
                'submit_button_id' => 'create-task'
            ]
        ];
        yield 'Form data to edit a task' => [
            'data' => [
                'uri'              => '/tasks/1/edit',
                'form_name'        => 'edit_task',
                'csrf_token_id'    => 'edit_task_action',
                'submit_button_id' => 'edit-task'
            ]
        ];
        yield 'Form data to toggle a task' => [
            'data' => [
                'uri'              => '/tasks',
                'form_name'        => 'toggle_task_1',
                'csrf_token_id'    => 'toggle_task_action',
                'submit_button_id' => 'toggle-task-1'
            ]
        ];
        // Add other forms here
    }

    /**
     * Provide controller methods forms data.
     *
     * @return \Generator
     */
    public function provideToggleActionData(): \Generator
    {
        yield 'Task with id 1 was done before toggle' => [
            'data' => [
                'task_id'                 => 1,
                'success_message'         => 'non terminée',
                'new_toggle_button_label' => 'faite'
            ]
        ];
        yield 'Task with id 2 was undone before toggle' => [
            'data' => [
                'task_id'                 => 2,
                'success_message'         => 'faite',
                'new_toggle_button_label' => 'non terminée'
            ]
        ];
    }

    /**
     * Check that a user should authenticate to be able to create a task.
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
        static::assertAccessIsDenied($this->client);
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
        $buttonCrawlerNode = $crawler->selectButton($data['submit_button_id']);
        $form = $buttonCrawlerNode->form();
        // Check that CSRF token value is present among form values
        static::assertTrue(\in_array($csrfToken->getValue(), $form->getValues()));
        // Get consistency by keeping the same CSRF token name for all forms
        static::assertTrue($form->has($data['form_name'] . '[_token]'));
        // Pass a wrong token
        $form[$data['form_name'] . '[_token]'] = 'Wrong CSRF token';
        $crawler = $this->client->submit($form);
         // Check that CSRF token cannot be tampered!
        static::assertCount(1, $crawler->filter('div.alert-danger'));
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
            'create_task[title]'   => 'Nouvelle tâche',
            'create_task[content]' => 'Ceci est un contenu de nouvelle tâche.'
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
            'create_task[title]' => 'Nouvelle tâche ' . $uniqueID,
            'create_task[content]' => 'Ceci est un contenu de nouvelle tâche.'
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
        $this->client->request('GET', '/tasks/1/edit');
        $this->client->submitForm('Modifier', [
            'edit_task[title]'   => 'Tâche modifiée',
            'edit_task[content]' => 'Ceci est un changement de contenu de la tâche.'
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
     * @dataProvider provideToggleActionData
     *
     * @param array $data
     *
     * @return void
     */
    public function testExistingTaskCanBeToggled(array $data): void
    {
        $this->loginUser();
        $crawler = $this->client->request('GET', '/tasks');
        /** @var ObjectRepository $taskRepository */
        $taskRepository = static::$container->get('doctrine')->getRepository(Task::class);
        $existingTask = $taskRepository->find($data['task_id']);
        // Get task "isDone" state before toggle
        $previousIsDoneValue = $existingTask->isDone();
        // No data is submitted during toggle action, only the task id is taken into account!
        $form = $crawler->selectButton('toggle-task-' . $data['task_id'])->form();
        $this->client->submit($form);
        $toggledTask = $taskRepository->find($data['task_id']);
        // Check that isDone" state is inverse after toggle
        static::assertSame(!$previousIsDoneValue, $toggledTask->isDone());
    }

    /**
     * Check that "toggle" action reverses texts status correctly depending on "isDone" state.
     *
     * @dataProvider provideToggleActionData
     *
     * @param array $data
     *
     * @return void
     */
    public function testExistingTaskWasToggledWithCorrectTextsStatus(array $data): void
    {
        $this->loginUser();
        $crawler = $this->client->request('GET', '/tasks');
        /** @var ObjectRepository $taskRepository */
        $taskRepository = static::$container->get('doctrine')->getRepository(Task::class);
        $existingTask = $taskRepository->find($data['task_id']);
        // No data is submitted during toggle action, only the task id is taken into account!
        $form = $crawler->selectButton('toggle-task-' . $data['task_id'])->form();
        $this->client->submit($form);
        static::assertTrue($this->client->getResponse()->isRedirect('/tasks'));
        $crawler = $this->client->followRedirect();
        // Check flash success message content
        static::assertSame(
            sprintf(
                'Superbe ! La tâche "%s" a bien été marquée comme %s.',
                $existingTask->getTitle(),
                $data['success_message']
            ),
            trim($crawler->filter('div.alert-success')->text(null, false))
        );
        // Check corresponding toggle button correct label
        static::assertSame(
            'Marquer comme ' . $data['new_toggle_button_label'],
            trim($crawler->filter('#toggle-task-' . $data['task_id'])->text(null, false))
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
        $this->client->request('GET', '/tasks/1/edit');
        /** @var ObjectRepository $taskRepository */
        $taskRepository = static::$container->get('doctrine')->getRepository(Task::class);
        $existingTask = $taskRepository->find(1);
        $crawler =$this->client->submitForm('Modifier', [
            'edit_task[title]'   => $existingTask->getTitle(),
            'edit_task[content]' => $existingTask->getContent()
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
        $this->client->request('GET', '/tasks/1/edit');
        $this->client->submitForm('Modifier', [
            'edit_task[title]'   => 'Tâche modifiée',
            'edit_task[content]' => 'Ceci est un changement de contenu de la tâche.'
        ], 'POST');
        /** @var ObjectRepository $taskRepository */
        $taskRepository = static::$container->get('doctrine')->getRepository(Task::class);
        $newTask = $taskRepository->find(1);
        // Check that author remained unchanged after update ("null" since defined without author by default)
        static::assertSame(null, $newTask->getAuthor());
        // Check that authenticated user is set as the last editor
        static::assertEquals($testUser->getId(), $newTask->getLastEditor()->getId());
    }
}