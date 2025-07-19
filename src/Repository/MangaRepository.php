<?php

namespace App\Repository;

use App\Entity\Manga;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Manga>
 */
class MangaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Manga::class);
    }

    public function save(Manga $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Manga $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findBySlug(string $slug): ?Manga
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function searchMangas(string $query, array $filters = [], int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.titre LIKE :exactQuery OR m.titreOriginal LIKE :exactQuery')
            ->orWhere('m.titre LIKE :query OR m.titreOriginal LIKE :query')
            ->orWhere('m.synopsis LIKE :query')
            ->setParameter('exactQuery', $query)
            ->setParameter('query', '%' . $query . '%')
            ->andWhere('m.statut = :statut')
            ->setParameter('statut', 1)
            ->addSelect('
                CASE 
                    WHEN m.titre = :exactQuery OR m.titreOriginal = :exactQuery THEN 3
                    WHEN m.titre LIKE :startQuery OR m.titreOriginal LIKE :startQuery THEN 2
                    WHEN m.titre LIKE :query OR m.titreOriginal LIKE :query THEN 1
                    ELSE 0
                END as HIDDEN relevance
            ')
            ->setParameter('startQuery', $query . '%')
            ->orderBy('relevance', 'DESC')
            ->addOrderBy('m.moyenneNotes', 'DESC')
            ->addOrderBy('m.dateAjout', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function createQueryBuilderWithFilters(string $search = '', string $auteur = '', string $year = '', string $sort = 'date_desc'): QueryBuilder
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.statut = :statut')
            ->setParameter('statut', 1);

        if (!empty($search)) {
            $qb->andWhere('(m.titre LIKE :search OR m.titreOriginal LIKE :search OR m.synopsis LIKE :search)')
                ->setParameter('search', '%' . $search . '%');
        }

        if (!empty($auteur)) {
            $qb->andWhere('m.auteur LIKE :auteur')
                ->setParameter('auteur', '%' . $auteur . '%');
        }

        if (!empty($year)) {
            $qb->andWhere('m.annee = :year')
                ->setParameter('year', $year);
        }

        switch ($sort) {
            case 'date_asc':
                $qb->orderBy('m.dateAjout', 'ASC');
                break;
            case 'title_asc':
                $qb->orderBy('m.titre', 'ASC');
                break;
            case 'title_desc':
                $qb->orderBy('m.titre', 'DESC');
                break;
            case 'year_desc':
                $qb->orderBy('m.annee', 'DESC');
                break;
            case 'year_asc':
                $qb->orderBy('m.annee', 'ASC');
                break;
            case 'rating_desc':
                $qb->orderBy('m.moyenneNotes', 'DESC');
                break;
            case 'just_added':
                $qb->orderBy('m.dateAjout', 'DESC');
                break;
            case 'date_desc':
            default:
                $qb->orderBy('m.dateAjout', 'DESC');
                break;
        }

        return $qb;
    }

    public function getAllAuthors(): array
    {
        $result = $this->createQueryBuilder('m')
            ->select('DISTINCT m.auteur')
            ->where('m.auteur IS NOT NULL')
            ->andWhere('m.auteur != :empty')
            ->andWhere('m.statut = :statut')
            ->setParameter('empty', '')
            ->setParameter('statut', 1)
            ->orderBy('m.auteur', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'auteur');
    }

    public function getAllYears(): array
    {
        $result = $this->createQueryBuilder('m')
            ->select('DISTINCT m.annee')
            ->where('m.annee IS NOT NULL')
            ->andWhere('m.annee != :empty')
            ->andWhere('m.statut = :statut')
            ->setParameter('empty', '')
            ->setParameter('statut', 1)
            ->orderBy('m.annee', 'DESC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'annee');
    }

    public function getTotalCount(): int
    {
        return $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->where('m.statut = :statut')
            ->setParameter('statut', 1)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findTopRated(int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.statut = :statut')
            ->andWhere('m.moyenneNotes > 0')
            ->setParameter('statut', 1)
            ->orderBy('m.moyenneNotes', 'DESC')
            ->addOrderBy('m.nbReviews', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByAuthor(string $author, int $limit = 20): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.auteur LIKE :author')
            ->andWhere('m.statut = :statut')
            ->setParameter('author', '%' . $author . '%')
            ->setParameter('statut', 1)
            ->orderBy('m.dateAjout', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function createQueryBuilderForTopRated(): QueryBuilder
    {
        return $this->createQueryBuilder('m')
            ->where('m.statut = :statut')
            ->andWhere('m.moyenneNotes > 0')
            ->setParameter('statut', 1)
            ->orderBy('m.moyenneNotes', 'DESC')
            ->addOrderBy('m.nbReviews', 'DESC');
    }

    public function createQueryBuilderForAuthor(string $author): QueryBuilder
    {
        return $this->createQueryBuilder('m')
            ->where('m.auteur LIKE :author')
            ->andWhere('m.statut = :statut')
            ->setParameter('author', '%' . $author . '%')
            ->setParameter('statut', 1)
            ->orderBy('m.dateAjout', 'DESC');
    }

    public function createQueryBuilderForYear(int $year): QueryBuilder
    {
        return $this->createQueryBuilder('m')
            ->where('m.annee = :year')
            ->andWhere('m.statut = :statut')
            ->setParameter('year', $year)
            ->setParameter('statut', 1)
            ->orderBy('m.dateAjout', 'DESC');
    }
}