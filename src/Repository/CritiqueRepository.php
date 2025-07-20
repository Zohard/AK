<?php

namespace App\Repository;

use App\Entity\Critique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Critique>
 */
class CritiqueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Critique::class);
    }

    public function findRecentCritiques(int $limit): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.statut = :status')
            ->setParameter('status', 1) // Assuming 1 means approved
            ->orderBy('c.dateCreation', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getCritiqueCountByMonth(): array
    {
        return [];
    }

    public function getAverageRating(): float
    {
        return 0.0;
    }

    public function createQueryBuilderWithFilters(string $type = '', string $sort = 'date_desc')
    {
        $qb = $this->createQueryBuilder('c');

        if ($type) {
            $qb->andWhere('c.type = :type')
               ->setParameter('type', $type);
        }

        switch ($sort) {
            case 'rating_desc':
                $qb->orderBy('c.note', 'DESC');
                break;
            case 'votes_desc':
                $qb->orderBy('c.votesPositifs', 'DESC');
                break;
            default:
                $qb->orderBy('c.dateCreation', 'DESC');
        }

        return $qb;
    }

    public function getTotalCount(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.statut = :status')
            ->setParameter('status', 1) // Assuming 1 means approved
            ->getQuery()
            ->getSingleScalarResult();
    }
}