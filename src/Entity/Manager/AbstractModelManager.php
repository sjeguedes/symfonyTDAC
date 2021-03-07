<?php

declare(strict_types=1);

namespace App\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractModelManager
 *
 * Manage all model manager common actions.
 */
abstract class AbstractModelManager implements ModelManagerInterface
{
    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * AbstractModelManager constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param LoggerInterface        $logger
     */
    public function __construct(EntityManagerInterface $entityManager, LoggerInterface $logger)
    {
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function getPersistenceLayer(): object
    {
        return $this->entityManager;
    }
}