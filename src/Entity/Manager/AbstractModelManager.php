<?php

declare(strict_types=1);

namespace App\Entity\Manager;

use Doctrine\ORM\EntityManagerInterface;

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
    protected EntityManagerInterface $entityManager;

    /**
     * AbstractModelManager constructor.
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function saveData(object $dataModel = null): bool
    {
        try {
            if (null !== $dataModel) {
                $this->entityManager->persist($dataModel);
            }
            $this->entityManager->flush();

            return true;
        } catch(\Exception $exception) {
            return false;
        }
    }
}