<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Manager;

use App\Entity\Manager\AbstractDataModelManager;
use App\Entity\Manager\DataModelManagerInterface;
use App\Tests\Unit\Helpers\CustomAssertionsTestCaseTrait;
use App\View\Builder\ViewModelBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractViewModelBuilderTest
 *
 * Manage unit tests for view model builders common logic declared in AbstractViewModelBuilder class.
 */
class AbstractViewModelBuilderTest extends TestCase
{
    use CustomAssertionsTestCaseTrait;

    /**
     * @var MockObject|EntityManagerInterface|null
     */
    private ?EntityManagerInterface $entityManager;

    /**
     * @var ViewModelBuilderInterface|null
     */
    private ?ViewModelBuilderInterface $dataModelManager;

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