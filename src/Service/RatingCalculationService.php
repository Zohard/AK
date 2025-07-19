<?php

namespace App\Service;

use App\Entity\Anime;
use App\Entity\Manga;
use App\Repository\CritiqueRepository;

class RatingCalculationService
{
    public function __construct(
        private CritiqueRepository $critiqueRepository
    ) {}

    public function calculateAnimeRating(Anime $anime): array
    {
        $critiques = $this->critiqueRepository->findApprovedCritiquesForAnime($anime->getId());
        
        if (empty($critiques)) {
            return [
                'average' => null,
                'total_votes' => 0,
                'distribution' => []
            ];
        }

        $totalScore = 0;
        $totalVotes = count($critiques);
        $distribution = array_fill(1, 20, 0);

        foreach ($critiques as $critique) {
            $note = $critique->getNote();
            $totalScore += $note;
            $distribution[$note]++;
        }

        $average = round($totalScore / $totalVotes, 2);

        return [
            'average' => $average,
            'total_votes' => $totalVotes,
            'distribution' => $distribution
        ];
    }

    public function calculateMangaRating(Manga $manga): array
    {
        $critiques = $this->critiqueRepository->findApprovedCritiquesForManga($manga->getId());
        
        if (empty($critiques)) {
            return [
                'average' => null,
                'total_votes' => 0,
                'distribution' => []
            ];
        }

        $totalScore = 0;
        $totalVotes = count($critiques);
        $distribution = array_fill(1, 20, 0);

        foreach ($critiques as $critique) {
            $note = $critique->getNote();
            $totalScore += $note;
            $distribution[$note]++;
        }

        $average = round($totalScore / $totalVotes, 2);

        return [
            'average' => $average,
            'total_votes' => $totalVotes,
            'distribution' => $distribution
        ];
    }

    public function calculateWeightedRating(int $votes, float $average, int $minimumVotes = 10): float
    {
        if ($votes < $minimumVotes) {
            return $average;
        }

        // Bayesian average - prevents items with few votes from ranking too high
        $globalAverage = $this->getGlobalAverageRating();
        $weight = $minimumVotes;
        
        return ($weight * $globalAverage + $votes * $average) / ($weight + $votes);
    }

    public function getPopularityScore(int $votes, int $views, int $listEntries): float
    {
        // Custom popularity algorithm
        $voteWeight = 0.4;
        $viewWeight = 0.3;
        $listWeight = 0.3;
        
        $normalizedVotes = $this->normalizeValue($votes, 0, 1000);
        $normalizedViews = $this->normalizeValue($views, 0, 10000);
        $normalizedLists = $this->normalizeValue($listEntries, 0, 500);
        
        return ($normalizedVotes * $voteWeight) + 
               ($normalizedViews * $viewWeight) + 
               ($normalizedLists * $listWeight);
    }

    private function normalizeValue(int $value, int $min, int $max): float
    {
        if ($max <= $min) {
            return 0;
        }
        
        return min(1, max(0, ($value - $min) / ($max - $min)));
    }

    private function getGlobalAverageRating(): float
    {
        // This should be cached and updated periodically
        return $this->critiqueRepository->getGlobalAverageRating() ?? 10.0;
    }

    public function getScoreColor(float $score): string
    {
        if ($score >= 16) return 'success';
        if ($score >= 14) return 'primary';
        if ($score >= 12) return 'info';
        if ($score >= 10) return 'warning';
        return 'danger';
    }

    public function getScoreLabel(float $score): string
    {
        if ($score >= 18) return 'Chef-d\'œuvre';
        if ($score >= 16) return 'Excellent';
        if ($score >= 14) return 'Très bon';
        if ($score >= 12) return 'Bon';
        if ($score >= 10) return 'Correct';
        if ($score >= 8) return 'Moyen';
        if ($score >= 6) return 'Médiocre';
        if ($score >= 4) return 'Mauvais';
        return 'Désastreux';
    }
}