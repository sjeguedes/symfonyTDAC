<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form\Handler\Helpers;

use App\Entity\Manager\DataModelManagerInterface;
use App\Entity\Manager\TaskDataModelManager;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractTaskFormHandlerTestCase
 *
 * Manage all task form handlers unit tests common actions as helper.
 */
abstract class AbstractTaskFormHandlerTestCase extends AbstractFormHandlerTestCase
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
     * Instantiate a task manager instance with mocked dependencies.
     *
     * @param EntityManagerInterface $entityManager
     *
     * @return DataModelManagerInterface|TaskDataModelManager
     */
    protected function setTaskDataModelManager(EntityManagerInterface $entityManager): DataModelManagerInterface
    {
        // Use a task manager instance to be able to make entity manager throwing an exception
        $logger = static::createMock(LoggerInterface::class);

        return new TaskDataModelManager($entityManager, $logger);
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