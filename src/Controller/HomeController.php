<?php

namespace App\Controller;

use App\Repository\AnimeRepository;
use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    public function __construct(
        private AnimeRepository $animeRepository,
        private MangaRepository $mangaRepository
    ) {}

    private function getRealData()
    {
        $jsonPath = __DIR__ . '/../../data/exported_data.json';
        
        if (file_exists($jsonPath)) {
            $jsonContent = file_get_contents($jsonPath);
            $data = json_decode($jsonContent, true);
            
            if ($data) {
                return $data;
            }
        }
        
        // Fallback to basic structure if file doesn't exist
        return array(
            'animeCount' => 0,
            'mangaCount' => 0,
            'topAnimes' => array(),
            'topMangas' => array(),
            'animes' => array(),
            'mangas' => array()
        );
    }

    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        try {
            $homeData = [
                'top_animes' => $this->animeRepository->findTopRated(5),
                'top_mangas' => $this->mangaRepository->findTopRated(5),
                'recent_animes' => $this->animeRepository->findRecentlyAdded(6),
                'recent_mangas' => $this->mangaRepository->findRecentlyAdded(6),
                'recent_critiques' => [], // TODO: Add critique repository when available
                'stats' => [
                    'total_animes' => $this->animeRepository->getTotalCount(),
                    'total_mangas' => $this->mangaRepository->getTotalCount(),
                    'total_critiques' => 0, // TODO: Add critique repository when available
                ]
            ];
        } catch (\Exception $e) {
            // Fallback if database is not available
            $homeData = [
                'top_animes' => [],
                'top_mangas' => [],
                'recent_animes' => [],
                'recent_mangas' => [],
                'recent_critiques' => [],
                'stats' => [
                    'total_animes' => 0,
                    'total_mangas' => 0,
                    'total_critiques' => 0,
                ]
            ];
        }

        return $this->render('home/index.html.twig', $homeData);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }

    #[Route('/stats', name: 'app_stats')]
    public function stats(): Response
    {
        try {
            $stats = [
                'animes' => [
                    'total' => $this->animeRepository->getTotalCount(),
                    'top_rated' => $this->animeRepository->findTopRated(10)
                ],
                'mangas' => [
                    'total' => $this->mangaRepository->getTotalCount(),
                    'top_rated' => $this->mangaRepository->findTopRated(10)
                ]
            ];
        } catch (\Exception $e) {
            $stats = [
                'animes' => ['total' => 0, 'top_rated' => []],
                'mangas' => ['total' => 0, 'top_rated' => []]
            ];
        }

        return $this->render('home/stats.html.twig', [
            'stats' => $stats
        ]);
    }

    #[Route('/contact', name: 'app_contact')]
    public function contact(): Response
    {
        return $this->render('home/contact.html.twig');
    }
}