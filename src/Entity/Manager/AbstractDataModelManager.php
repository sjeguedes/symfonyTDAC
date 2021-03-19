<?php

declare(strict_types=1);

namespace App\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class AbstractDataModelManager
 *
 * Manage all data model manager common actions.
 */
abstract class AbstractDataModelManager implements DataModelManagerInterface
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
     * AbstractDataModelManager constructor.
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

    /**
     * Save entity new or updated data.
     *
     * IMPORTANT: add particular removal method implementation later!
     *
     * @param object $entity
     * @param string $errorMessageIntro
     * @param bool   $shouldPersist
     *
     * @return bool
     */
    protected function save(object $entity, string $errorMessageIntro, bool $shouldPersist = false): bool
    {
        try {
            !$shouldPersist ?: $this->getPersistenceLayer()->persist($entity);
            $this->getPersistenceLayer()->flush();

            return true;
        } catch (\Exception $exception) {
            $this->logger->error($errorMessageIntro . ': ' . $exception->getMessage());

            return false;
        }
    }

    /**
     * Remove entity data.
     *
     * @param object $entity
     * @param string $errorMessageIntro
     *
     * @return bool
     */
    protected function remove(object $entity, string $errorMessageIntro): bool
    {
        try {
            $this->getPersistenceLayer()->remove($entity);
            $this->getPersistenceLayer()->flush();

            return true;
        } catch (\Exception $exception) {
            $this->logger->error($errorMessageIntro . ': ' . $exception->getMessage());

            return false;
        }
    }
}