<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\Task;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Class TaskControllerTest
 *
 * Define functional tests for TaskController.
 */
class TaskControllerTest extends AbstractControllerTest
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
                'uri'                 => '/tasks/create',
                'form_name'           => 'create_task',
                'csrf_token_id'       => 'create_task_action',
                'submit_button_label' => 'Ajouter'
            ]
        ];
        // Add other forms here
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
        $buttonCrawlerNode = $crawler->selectButton($data['submit_button_label']);
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
        $crawler = $this->client->submitForm('Ajouter', [
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
        $crawler = $this->client->submitForm('Ajouter', [
            'create_task[title]' => 'Nouvelle tâche ' . $uniqueID,
            'create_task[content]' => 'Ceci est un contenu de nouvelle tâche.'
        ], 'POST');
        /** @var ObjectRepository $taskRepository */
        $taskRepository = static::$container->get('doctrine')->getRepository(Task::class);
        $newTask = $taskRepository->findOneBy(['title' => 'Nouvelle tâche ' . $uniqueID]);
        // Check that authenticated is the new task author
        static::assertEquals($testUser->getId(), $newTask->getAuthor()->getId());
    }
}