<?php

namespace App\Service;

use App\Entity\Manga;
use App\Entity\User;
use App\Repository\MangaRepository;

class MangaService
{
    public function __construct(
        private MangaRepository $mangaRepository
    ) {}

    public function findSimilarMangas(Manga $manga, int $limit): array
    {
        return [];
    }

    public function getUserMangaStatus(User $user, Manga $manga): ?string
    {
        return null;
    }
}