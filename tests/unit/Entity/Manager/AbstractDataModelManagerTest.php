<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Manager;

use App\Entity\Manager\AbstractDataModelManager;
use App\Entity\Manager\DataModelManagerInterface;
use App\Tests\Unit\Helpers\CustomAssertionsTestCaseTrait;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractDataModelManagerTest
 *
 * Manage unit tests for data model managers common logic declared in AbstractDataModelManager class.
 */
class AbstractDataModelManagerTest extends TestCase
{
    use CustomAssertionsTestCaseTrait;

    /**
     * @var MockObject|EntityManagerInterface|null
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * @var MockObject|LoggerInterface|null
     */
    private ?LoggerInterface $logger;

    /**
     * @var DataModelManagerInterface|null
     */
    private ?DataModelManagerInterface $dataModelManager;

    /**
     * Setup needed instance(s).
     *
     * @return void
     *
     * @throws \Exception
     */
    public function setUp(): void
    {
        $this->entityManager = static::createMock(EntityManagerInterface::class);
        $this->logger = static::createMock(LoggerInterface::class);
        // Use an anonymous class to represent a concrete data model manager
        $this->dataModelManager = new class (
            $this->entityManager,
            $this->logger
        ) extends AbstractDataModelManager {};
    }

    /**
     * Check persistence layer correct implementation.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function testPersistenceLayerInstance(): void
    {
        $object = $this->dataModelManager->getPersistenceLayer();
        static::assertImplements(EntityManagerInterface::class, $object);
    }

    /**
     * Clear setup to free memory.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->entityManager = null;
        $this->logger = null;
        $this->dataModelManager = null;
    }
}