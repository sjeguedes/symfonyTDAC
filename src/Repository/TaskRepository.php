<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Task;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * TaskRepository class
 *
 * Manage queries in database as an entity data layer.
 */
class TaskRepository extends ServiceEntityRepository
{
    /**
     * TaskRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    /**
     * Find all the tasks or filter list by status depending on "isDone" state
     * as an array of data.
     *
     * Please note that no data is an hydrated object (scalar result) for a matter of performance.
     * Results have custom selected data to show in a view.
     *
     * @param string|null $withStatus
     * @return array
     */
    public function findList(string $withStatus = null): array
    {
        if (!\in_array($withStatus, [null, 'done', 'undone'])) {
            throw new \InvalidArgumentException('Task list status value is unexpected!');
        }

        $queryBuilder = $this->createQueryBuilder('t');
        $queryBuilder
            // Select essential data to use including MySQL date formatting thanks to Doctrine extension
            ->select([
                't.id', 't.title', 't.content', 't.isDone',
                "DATE_FORMAT(t.createdAt, '%d/%m/%Y') AS createdAt",
                "DATE_FORMAT(t.updatedAt, '%d/%m/%Y') AS updatedAt",
                'u1.username AS author', 'u2.username AS lastEditor'
            ])
            // A "left join" is made since an author can be set as "null" for old tasks (anonymous user)!
            ->leftJoin('t.author', 'u1', 'WITH', 't.author = u1.id')
            ->leftJoin('t.lastEditor', 'u2', 'WITH', 't.lastEditor = u2.id');
        // Filter by status if needed
        if (null !== $withStatus) {
            $queryBuilder
                ->andWhere('t.isDone = :isDone')
                ->setParameter('isDone', 'done' === $withStatus ? 1 : 0);
        }
        // Get tasks data without hydrating
        return $queryBuilder
            ->getQuery()
            ->getScalarResult();
    }
}
