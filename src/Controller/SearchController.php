<?php

namespace App\Controller;

use App\Repository\AnimeRepository;
use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    public function __construct(
        private AnimeRepository $animeRepository,
        private MangaRepository $mangaRepository
    ) {}

    #[Route('/search', name: 'search_global')]
    public function globalSearch(Request $request): Response
    {
        $query = $request->query->get('q', '');
        
        if (empty($query)) {
            return $this->render('search/results.html.twig', [
                'query' => $query,
                'animes' => [],
                'mangas' => [],
            ]);
        }

        // Search animes and mangas
        $animes = $this->animeRepository->searchAnimes($query, [], 20);
        $mangas = $this->mangaRepository->searchMangas($query, [], 20);

        return $this->render('search/results.html.twig', [
            'query' => $query,
            'animes' => $animes,
            'mangas' => $mangas,
        ]);
    }
}