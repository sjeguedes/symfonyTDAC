<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Handler\Helpers;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\Manager\UserDataModelManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractUserFormHandlerTestCase
 *
 * Manage all user form handlers unit tests common actions as helper.
 */
abstract class AbstractUserFormHandlerTestCase extends AbstractFormHandlerTestCase
{
    /**
     * Setup needed instance(s).
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Instantiate a user manager instance with mocked dependencies.
     *
     * @param EntityManagerInterface $entityManager
     *
     * @return DataModelManagerInterface|UserDataModelManager
     */
    protected function setUserDataModelManager(EntityManagerInterface $entityManager): DataModelManagerInterface
    {
        // Use a user manager instance to be able to make entity manager throwing an exception
        $logger = static::createMock(LoggerInterface::class);

        return new UserDataModelManager($entityManager, $logger);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }
}