<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller;

use App\Entity\User;
use App\Tests\Functional\Controller\Helpers\AbstractControllerWebTestCase;
use Doctrine\ORM\Events;
use Doctrine\Persistence\ObjectRepository;
use Symfony\Component\Security\Csrf\CsrfToken;

/**
 * Class UserControllerTest
 *
 * Define functional tests for UserController.
 */
class UserControllerTest extends AbstractControllerWebTestCase
{
    /**
     * Define form fields base names.
     */
    private const BASE_FORM_FIELDS_NAMES = [
        'user_creation' => 'create_user[user]', // compound form
        'user_update'   => 'edit_user[user]', // compound form
        'user_deletion' => 'delete_user'
    ];

    /**
     * Provide controller methods URIs.
     *
     * @return array
     */
    public function provideURIs(): array
    {
        return [
            'List users'           => ['GET', '/users'],
            'Access user creation' => ['GET', '/users/create'],
            'Create a user'        => ['POST', '/users/create'],
            'Access user update'   => ['GET', '/users/1/edit'],
            'Update (Edit) a user' => ['POST', '/users/1/edit'],
            'Delete a user'        => ['DELETE', '/users/1/delete']
        ];
    }

    /**
     * Provide controller methods forms data.
     *
     * @return \Generator
     */
    public function provideFormsConfigurations(): \Generator
    {
        yield 'Gets form data to create a user' => [
            'data' => [
                'uri'              => '/users/create',
                'form_name'        => self::BASE_FORM_FIELDS_NAMES['user_creation'],
                'csrf_token_id'    => 'create_user_action',
                'submit_button_id' => 'create-user'
            ]
        ];
        yield 'Gets form data to edit a user' => [
            'data' => [
                'uri'              => '/users/1/edit',
                'form_name'        => self::BASE_FORM_FIELDS_NAMES['user_update'],
                'csrf_token_id'    => 'edit_user_action',
                'submit_button_id' => 'edit-user'
            ]
        ];
        yield 'Gets form data to delete a user' => [
            'data' => [
                'uri'              => '/users',
                'form_name'        => self::BASE_FORM_FIELDS_NAMES['user_deletion']. '_1',
                'csrf_token_id'    => 'delete_user_action',
                'submit_button_id' => 'delete-user-1'
            ]
        ];
        // Add other forms here
    }

    /**
     * Check that a user should authenticate to be able to perform other users actions.
     *
     * @dataProvider provideURIs
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    public function testUnauthenticatedUserCannotAccessUserRequests(string $method, string $uri): void
    {
        $this->client->request($method, $uri);
        // Use assertions with custom method
        static::assertAccessIsUnauthorizedWithTemporaryRedirection($this->client);
    }

    /**
     * Check that an authenticated user should have "admin" role to be able to perform other users actions.
     *
     * @dataProvider provideURIs
     *
     * @param string $method
     * @param string $uri
     *
     * @return void
     */
    public function testAuthenticatedUserCannotAccessUserRequestsWithoutAdminRole(string $method, string $uri): void
    {
        // Log in user without admin role
        $this->loginUser();
        $this->client->request($method, $uri);
        // Check forbidden access with response status code
        static::assertResponseStatusCodeSame(403);
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
        $this->loginAdmin();
        $crawler = $this->client->request('GET', $data['uri']);
        /** @var CsrfToken $csrfToken */
        $csrfToken = static::$container->get('security.csrf.token_manager')->getToken($data['csrf_token_id']);
        // Csrf token form is outside nested "user" form type!
        $csrfTokenFieldNameAttribute = str_replace('[user]', '', $data['form_name']) . '[_token]';
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
     * Check that users can be correctly listed.
     *
     * @return void
     */
    public function testUsersCanBeListed(): void
    {
        $this->loginAdmin();
        // Access full user list
        $crawler = $this->client->request('GET', '/users');
        static::assertSame(5, $crawler->filter('tr.user')->count());
    }

    /**
     * Check that a new user is correctly created.
     *
     * @return void
     */
    public function testNewUserCanBeCreated(): void
    {
        $this->loginAdmin();
        $this->client->request('GET', '/users/create');
        $this->client->submitForm('Ajouter', [
            self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[username]'         => 'utilisateur',
            self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[email]'            => 'utilisateur@test.fr',
            self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[roles]'            => 'ROLE_ADMIN, ROLE_USER',
            self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[password][first]'  => 'password_1A$',
            self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[password][second]' => 'password_1A$'
        ], 'POST');
        static::assertTrue($this->client->getResponse()->isRedirect('/users'));
        $crawler = $this->client->followRedirect();
        static::assertSame(
            'Superbe ! L\'utilisateur a bien été ajouté.',
            trim($crawler->filter('div.alert-success')->text(null, false))
        );
    }

    /**
     * Check that a new user has roles capabilities.
     *
     * @return void
     */
    public function testNewUserHasRoles(): void
    {
        $this->loginAdmin();
        $this->client->request('GET', '/users/create');
        $this->client->submitForm('Ajouter', [
            self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[username]'         => 'utilisateur',
            self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[email]'            => 'utilisateur@test.fr',
            self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[roles]'            => 'ROLE_ADMIN, ROLE_USER',
            self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[password][first]'  => 'password_1A$',
            self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[password][second]' => 'password_1A$'
        ], 'POST');
        /** @var ObjectRepository $userRepository */
        $userRepository = static::$container->get('doctrine')->getRepository(User::class);
        $newUser = $userRepository->findOneBy(['email' => 'utilisateur@test.fr']);
        // Check that new user has expected roles
        static::assertEquals(['ROLE_ADMIN', 'ROLE_USER'], $newUser->getRoles());
    }

    /**
     * Check that an existing user is correctly updated.
     *
     * @return void
     */
    public function testExistingUserCanBeUpdated(): void
    {
        $this->loginAdmin();
        // Get user with id 2
        // Exclude unique test admin account to avoid issue (during test if roles is changed to simple user!)
        $this->client->request('GET', '/users/2/edit');
        $this->client->submitForm('Modifier', [
            self::BASE_FORM_FIELDS_NAMES['user_update'] . '[username]'         => 'utilisateur modifié',
            self::BASE_FORM_FIELDS_NAMES['user_update'] . '[email]'            => 'utilisateur-modifie@test.fr',
            self::BASE_FORM_FIELDS_NAMES['user_update'] . '[roles]'            => 'ROLE_USER',
            self::BASE_FORM_FIELDS_NAMES['user_update'] . '[password][first]'  => 'password_2B$',
            self::BASE_FORM_FIELDS_NAMES['user_update'] . '[password][second]' => 'password_2B$'
        ], 'POST');
        static::assertTrue($this->client->getResponse()->isRedirect('/users'));
        $crawler = $this->client->followRedirect();
        static::assertSame(
            'Superbe ! L\'utilisateur a bien été modifié.',
            trim($crawler->filter('div.alert-success')->text(null, false))
        );
    }

    /**
     * Check that an existing user is correctly deleted.
     *
     * @return void
     */
    public function testExistingUserCanBeDeleted(): void
    {
        $this->loginAdmin();
        $crawler = $this->client->request('GET', '/users');
        /** @var ObjectRepository $userRepository */
        $userRepository = static::$container->get('doctrine')->getRepository(User::class);
        // Get user with id 2
        // Exclude unique test admin account to avoid issue (during test if roles is changed to simple user!)
        $existingUser = $userRepository->find(2);
        static::assertInstanceOf(User::class, $existingUser);
        // No data is submitted during deletion action, only the user id is taken into account!
        $form = $crawler->selectButton('delete-user-2')->form();
        $this->client->submit($form);
        $deletedUser = $userRepository->find(2);
        // Check that user with id "$randomId" does not exist anymore
        static::assertSame(null, $deletedUser);
    }

    /**
     * Check that an existing user cannot be updated without form inputs modification (compared to initial data).
     *
     * @return void
     */
    public function testExistingUserCannotBeUpdatedWithoutFormInputsChanges(): void
    {
        $this->loginAdmin();
        // Get user with id 2
        // Exclude unique test admin account to avoid issue (during test if roles is changed to simple user!)
        $this->client->request('GET', '/users/2/edit');
        /** @var ObjectRepository $userRepository */
        $userRepository = static::$container->get('doctrine')->getRepository(User::class);
        $existingUser = $userRepository->find(2);
        $crawler =$this->client->submitForm('Modifier', [
            self::BASE_FORM_FIELDS_NAMES['user_update'] . '[username]'         => $existingUser->getUsername(),
            self::BASE_FORM_FIELDS_NAMES['user_update'] . '[email]'            => $existingUser->getEmail(),
            self::BASE_FORM_FIELDS_NAMES['user_update'] . '[roles]'            => implode(', ', $existingUser->getRoles()),
            self::BASE_FORM_FIELDS_NAMES['user_update'] . '[password][first]'  => 'password_' . 2 . 'A$',
            self::BASE_FORM_FIELDS_NAMES['user_update'] . '[password][second]' => 'password_' . 2 . 'A$'
        ], 'POST');
        static::assertFalse($this->client->getResponse()->isRedirect('/users'));
        static::assertSame(
            'Surprenant ! Aucun changement n\'a été effectué.',
            trim($crawler->filter('div.alert-warning')->text(null, false))
        );
    }

    /**
     * Check that technical error (database operations failure) is taken into account
     * to improve UX during user creation.
     *
     * @return void
     */
    public function testTechnicalErrorIsTakenIntoAccountOnUserCreationORMFailure(): void
    {
        $this->loginAdmin();
        // Call the request
        $this->client->request('GET', '/users/create');
        foreach ([Events::postPersist, Events::onFlush] as $eventName) {
            // Simulate ORM exception thanks to particular test Doctrine listener
            $this->makeEntityManagerThrowExceptionOnORMOperations($eventName);
            // Submit the form
            $crawler = $this->client->submitForm('Ajouter', [
                self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[username]' => 'utilisateur',
                self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[email]' => 'utilisateur@test.fr',
                self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[roles]' => 'ROLE_ADMIN, ROLE_USER',
                self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[password][first]' => 'password_1A$',
                self::BASE_FORM_FIELDS_NAMES['user_creation'] . '[password][second]' => 'password_1A$'
            ], 'POST');
            // Check that no redirection is made to user list
            static::assertFalse($this->client->getResponse()->isRedirect('/users'));
            // Ensure correct UX is displayed when ORM operation on entity failed
            static::assertSame(
                'Oops ! Un problème est survenu !',
                trim($crawler->filter('div.alert-danger')->text(null, false))
            );
        }
    }

    /**
     * Check that technical error (database operations failure) is taken into account
     * to improve UX during user update.
     *
     * @return void
     */
    public function testTechnicalErrorIsTakenIntoAccountOnUserUpdateORMFailure(): void
    {
        $this->loginAdmin();
        // Get user with id 2
        // Exclude unique test admin account to avoid issue (during test if roles is changed to simple user!)
        // Call the request
        $this->client->request('GET', '/users/2/edit');
        foreach ([Events::postUpdate, Events::onFlush] as $eventName) {
            // Simulate ORM exception thanks to particular test Doctrine listener
            $this->makeEntityManagerThrowExceptionOnORMOperations($eventName);
            // Submit the form
            $crawler = $this->client->submitForm('Modifier', [
                self::BASE_FORM_FIELDS_NAMES['user_update'] . '[username]'         => 'utilisateur modifié',
                self::BASE_FORM_FIELDS_NAMES['user_update'] . '[email]'            => 'utilisateur-modifie@test.fr',
                self::BASE_FORM_FIELDS_NAMES['user_update'] . '[roles]'            => 'ROLE_USER',
                self::BASE_FORM_FIELDS_NAMES['user_update'] . '[password][first]'  => 'password_2B$',
                self::BASE_FORM_FIELDS_NAMES['user_update'] . '[password][second]' => 'password_2B$'
            ], 'POST');
            // Check that no redirection is made to user list
            static::assertFalse($this->client->getResponse()->isRedirect('/users'));
            // Ensure correct UX is displayed when ORM operation on entity failed
            static::assertSame(
                'Oops ! Un problème est survenu !',
                trim($crawler->filter('div.alert-danger')->text(null, false))
            );
        }
    }

    /**
     * Check that technical error (database operations failure) is taken into account
     * to improve UX during user deletion.
     *
     * @return void
     */
    public function testTechnicalErrorIsTakenIntoAccountOnUserDeletionORMFailure(): void
    {
        $this->loginAdmin();
        // Get user with id 2
        // Exclude unique test admin account to avoid issue (during test if roles is changed to simple user!)
        // Call the request
        $crawler = $this->client->request('GET', '/users');
        foreach ([Events::postRemove, Events::onFlush] as $eventName) {
            // Simulate ORM exception thanks to particular test Doctrine listener
            $this->makeEntityManagerThrowExceptionOnORMOperations($eventName);
            // No data is submitted during deletion action, only the user id is taken into account!
            $form = $crawler->selectButton('delete-user-2')->form();
            // Submit the form
            $crawler = $this->client->submit($form);
            // Check that no redirection is made to user list
            static::assertFalse($this->client->getResponse()->isRedirect('/users'));
            // Ensure correct UX is displayed when ORM operation on entity failed
            static::assertSame(
                'Oops ! Un problème est survenu !',
                trim($crawler->filter('div.alert-danger')->text(null, false))
            );
        }
    }
}