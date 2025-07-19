<?php

namespace App\Service;

use App\Entity\Anime;
use App\Entity\User;
use App\Repository\AnimeRepository;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class AnimeService
{
    public function __construct(
        private AnimeRepository $animeRepository,
        private CacheInterface $cache,
        private RatingCalculationService $ratingService
    ) {}

    public function findSimilarAnimes(Anime $anime, int $limit = 6): array
    {
        $cacheKey = "similar_animes_{$anime->getId()}_{$limit}";
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($anime, $limit) {
            $item->expiresAfter(3600); // 1 hour

            // Find similar animes based on genre, tags, and year
            return $this->animeRepository->findSimilarAnimes($anime, $limit);
        });
    }

    public function getUserAnimeStatus(User $user, Anime $anime): ?string
    {
        return $this->animeRepository->getUserAnimeStatus($user->getId(), $anime->getId());
    }

    public function updateAnimeRating(Anime $anime): void
    {
        $newRating = $this->ratingService->calculateAnimeRating($anime);
        $anime->setNoteGenerale($newRating['average']);
        $anime->setNbVotes($newRating['total_votes']);
        
        // Clear cache for this anime
        $this->cache->delete("anime_rating_{$anime->getId()}");
        $this->cache->delete("similar_animes_{$anime->getId()}_6");
    }

    public function getAnimeStatistics(Anime $anime): array
    {
        $cacheKey = "anime_stats_{$anime->getId()}";
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($anime) {
            $item->expiresAfter(1800); // 30 minutes

            return [
                'total_critiques' => $anime->getCritiques()->count(),
                'avg_rating' => $anime->getNoteGenerale(),
                'rating_distribution' => $this->animeRepository->getRatingDistribution($anime->getId()),
                'user_list_stats' => $this->animeRepository->getUserListStats($anime->getId()),
                'popularity_rank' => $this->animeRepository->getPopularityRank($anime->getId()),
                'rating_rank' => $this->animeRepository->getRatingRank($anime->getId())
            ];
        });
    }

    public function searchAnimes(string $query, array $filters = [], int $limit = 20): array
    {
        return $this->animeRepository->searchAnimes($query, $filters, $limit);
    }

    public function getRandomAnimes(int $count = 12): array
    {
        $cacheKey = "random_animes_{$count}";
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($count) {
            $item->expiresAfter(300); // 5 minutes cache for random selection

            return $this->animeRepository->getRandomAnimes($count);
        });
    }

    public function getTopAnimesByGenre(string $genre, int $limit = 10): array
    {
        $cacheKey = "top_animes_genre_{$genre}_{$limit}";
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($genre, $limit) {
            $item->expiresAfter(7200); // 2 hours

            return $this->animeRepository->getTopAnimesByGenre($genre, $limit);
        });
    }

    public function getSeasonalAnimes(string $season, int $year): array
    {
        $cacheKey = "seasonal_animes_{$season}_{$year}";
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($season, $year) {
            $item->expiresAfter(3600); // 1 hour

            return $this->animeRepository->getSeasonalAnimes($season, $year);
        });
    }

    public function incrementAnimeViews(Anime $anime): void
    {
        // Increment view count (if you add this field)
        // Could also track in separate analytics table
        $this->animeRepository->incrementViews($anime->getId());
    }

    public function generateSlug(string $title): string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
        
        // Ensure uniqueness
        $originalSlug = $slug;
        $counter = 1;
        while ($this->animeRepository->findBySlug($slug)) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }

    public function getRecommendationsForUser(User $user, int $limit = 12): array
    {
        $cacheKey = "user_recommendations_{$user->getId()}_{$limit}";
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($user, $limit) {
            $item->expiresAfter(3600); // 1 hour

            // Get recommendations based on user's anime list and ratings
            return $this->animeRepository->getRecommendationsForUser($user->getId(), $limit);
        });
    }
}