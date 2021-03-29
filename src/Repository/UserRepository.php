<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * UserRepository class
 *
 * Manage queries in database as a User entity data layer.
 */
class UserRepository extends ServiceEntityRepository
{
    /**
     * UserRepository constructor.
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Find all the users as an array of data.
     *
     * Please note that no data is used as hydrated object (scalar result) for a matter of performance.
     * Results have custom selected data to show in a view.
     *
     * @return array
     */
    public function findList(): array
    {
        $queryBuilder = $this->createQueryBuilder('u');
        // Get users data without hydrating
        return $queryBuilder
            // Select essential data to use
            ->select([
                'u.id', 'u.username', 'u.email'
            ])
            ->getQuery()
            ->getScalarResult();
    }
}
