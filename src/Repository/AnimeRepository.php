<?php

namespace App\Repository;

use App\Entity\Anime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Anime>
 */
class AnimeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anime::class);
    }

    public function findBySlug(string $slug): ?Anime
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function createQueryBuilderWithFilters(string $search = '', string $genre = '', string $year = '', string $sort = 'date_desc')
    {
        $qb = $this->createQueryBuilder('a');

        if ($search) {
            $qb->andWhere('a.titre LIKE :search OR a.titreOriginal LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // TODO: Implement genre filtering using tag relationships
        // if ($genre) {
        //     $qb->andWhere('a.genre LIKE :genre')
        //        ->setParameter('genre', '%' . $genre . '%');
        // }

        if ($year) {
            $qb->andWhere('a.annee = :year')
               ->setParameter('year', $year);
        }

        // Apply sorting
        switch ($sort) {
            case 'title_asc':
                $qb->orderBy('a.titre', 'ASC');
                break;
            case 'title_desc':
                $qb->orderBy('a.titre', 'DESC');
                break;
            case 'year_asc':
                $qb->orderBy('a.annee', 'ASC');
                break;
            case 'year_desc':
                $qb->orderBy('a.annee', 'DESC');
                break;
            case 'rating_desc':
                $qb->orderBy('a.noteGenerale', 'DESC');
                break;
            case 'date_asc':
                $qb->orderBy('a.dateAjout', 'ASC');
                break;
            case 'just_added':
                $qb->orderBy('a.dateAjout', 'DESC');
                break;
            default: // date_desc
                $qb->orderBy('a.dateAjout', 'DESC');
                break;
        }

        return $qb;
    }

    public function getAllGenres(): array
    {
        // TODO: Implement genre retrieval using tag relationships
        return [];
    }

    public function getAllYears(): array
    {
        $qb = $this->createQueryBuilder('a')
            ->select('a.annee')
            ->where('a.annee IS NOT NULL')
            ->groupBy('a.annee')
            ->orderBy('a.annee', 'DESC');

        $results = $qb->getQuery()->getResult();
        
        return array_column($results, 'annee');
    }

    public function getTotalCount(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getCritiquesForAnime(int $animeId, int $limit): array
    {
        return [];
    }

    public function createQueryBuilderForGenre(string $genre)
    {
        return $this->createQueryBuilder('a');
    }

    public function createQueryBuilderForYear(int $year)
    {
        return $this->createQueryBuilder('a');
    }

    public function createQueryBuilderForTopRated()
    {
        return $this->createQueryBuilder('a');
    }

    public function createQueryBuilderForSeason(string $season, int $year)
    {
        return $this->createQueryBuilder('a');
    }

    public function findSimilarAnimes(Anime $anime, int $limit): array
    {
        return [];
    }

    public function getUserAnimeStatus(int $userId, int $animeId): ?string
    {
        return null;
    }

    public function getRatingDistribution(int $animeId): array
    {
        return [];
    }

    public function getUserListStats(int $animeId): array
    {
        return [];
    }

    public function getPopularityRank(int $animeId): int
    {
        return 0;
    }

    public function getRatingRank(int $animeId): int
    {
        return 0;
    }

    public function searchAnimes(string $query, array $filters = [], int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.titre LIKE :exactQuery OR a.titreOriginal LIKE :exactQuery')
            ->orWhere('a.titre LIKE :query OR a.titreOriginal LIKE :query')
            ->orWhere('a.synopsis LIKE :query')
            ->setParameter('exactQuery', $query)
            ->setParameter('query', '%' . $query . '%')
            ->addSelect('
                CASE 
                    WHEN a.titre = :exactQuery OR a.titreOriginal = :exactQuery THEN 3
                    WHEN a.titre LIKE :startQuery OR a.titreOriginal LIKE :startQuery THEN 2
                    WHEN a.titre LIKE :query OR a.titreOriginal LIKE :query THEN 1
                    ELSE 0
                END as HIDDEN relevance
            ')
            ->setParameter('startQuery', $query . '%')
            ->orderBy('relevance', 'DESC')
            ->addOrderBy('a.noteGenerale', 'DESC')
            ->addOrderBy('a.dateAjout', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function getRandomAnimes(int $count = 12): array
    {
        return [];
    }

    public function getTopAnimesByGenre(string $genre, int $limit = 10): array
    {
        return [];
    }

    public function getSeasonalAnimes(string $season, int $year): array
    {
        return [];
    }

    public function incrementViews(int $animeId): void
    {
    }

    public function getRecommendationsForUser(int $userId, int $limit = 12): array
    {
        return [];
    }

    public function findRecentlyAdded(int $limit): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.dateAjout', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findTopRated(int $limit): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.noteGenerale IS NOT NULL')
            ->orderBy('a.noteGenerale', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getAnimeCountByYear(): array
    {
        return [];
    }

    public function getAnimeCountByGenre(): array
    {
        return [];
    }
}