<?php

namespace App\Controller;

use App\Entity\Anime;
use App\Repository\AnimeRepository;
use App\Service\AnimeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/animes')]
class AnimeController extends AbstractController
{
    public function __construct(
        private AnimeRepository $animeRepository,
        private AnimeService $animeService
    ) {}

    #[Route('/', name: 'anime_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $genre = $request->query->get('genre', '');
        $year = $request->query->get('year', '');
        $letter = $request->query->get('letter', '');
        $sort = $request->query->get('sort', 'date_desc');

        $queryBuilder = $this->animeRepository->createQueryBuilderWithFilters($search, $genre, $year, $sort);
        
        // Apply letter filter
        if ($letter) {
            if ($letter === '#') {
                $queryBuilder->andWhere('a.titre LIKE :num0 OR a.titre LIKE :num1 OR a.titre LIKE :num2 OR a.titre LIKE :num3 OR a.titre LIKE :num4 OR a.titre LIKE :num5 OR a.titre LIKE :num6 OR a.titre LIKE :num7 OR a.titre LIKE :num8 OR a.titre LIKE :num9')
                           ->setParameter('num0', '0%')
                           ->setParameter('num1', '1%')
                           ->setParameter('num2', '2%')
                           ->setParameter('num3', '3%')
                           ->setParameter('num4', '4%')
                           ->setParameter('num5', '5%')
                           ->setParameter('num6', '6%')
                           ->setParameter('num7', '7%')
                           ->setParameter('num8', '8%')
                           ->setParameter('num9', '9%');
            } else {
                $queryBuilder->andWhere('a.titre LIKE :letter')
                           ->setParameter('letter', $letter . '%');
            }
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            24,
            [
                'defaultSortFieldName' => 'a.dateAjout',
                'defaultSortDirection' => 'desc',
                'sortFieldParameterName' => 'sort_field',
                'sortDirectionParameterName' => 'sort_direction'
            ]
        );

        return $this->render('anime/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'genre' => $genre,
            'year' => $year,
            'letter' => $letter,
            'sort' => $sort,
            'genres' => $this->animeRepository->getAllGenres(),
            'years' => $this->animeRepository->getAllYears(),
        ]);
    }

    #[Route('/top', name: 'anime_top')]
    public function top(Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->animeRepository->createQueryBuilderForTopRated();

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            50
        );

        return $this->render('anime/top.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/genre/{genre}', name: 'anime_by_genre')]
    public function byGenre(string $genre, Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->animeRepository->createQueryBuilderForGenre($genre);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            24
        );

        return $this->render('anime/by_genre.html.twig', [
            'pagination' => $pagination,
            'genre' => $genre,
        ]);
    }

    #[Route('/year/{year}', name: 'anime_by_year', requirements: ['year' => '\d{4}'])]
    public function byYear(int $year, Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->animeRepository->createQueryBuilderForYear($year);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            24
        );

        return $this->render('anime/by_year.html.twig', [
            'pagination' => $pagination,
            'year' => $year,
        ]);
    }

    #[Route('/{slug}', name: 'anime_show', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(string $slug, Request $request): Response
    {
        $anime = $this->animeRepository->findBySlug($slug);

        if (!$anime) {
            throw $this->createNotFoundException('Anime not found');
        }

        // Get related data
        $critiques = $this->animeRepository->getCritiquesForAnime($anime->getId(), 10);
        $similarAnimes = $this->animeService->findSimilarAnimes($anime, 6);
        $screenshots = $anime->getScreenshots()->slice(0, 12);

        // Check if user has this anime in their list
        $userAnimeStatus = null;
        if ($this->getUser()) {
            $userAnimeStatus = $this->animeService->getUserAnimeStatus($this->getUser(), $anime);
        }

        return $this->render('anime/show.html.twig', [
            'anime' => $anime,
            'critiques' => $critiques,
            'similar_animes' => $similarAnimes,
            'screenshots' => $screenshots,
            'user_anime_status' => $userAnimeStatus,
        ]);
    }

    #[Route('/season/{season}/{year}', name: 'anime_by_season')]
    public function bySeason(string $season, int $year, Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->animeRepository->createQueryBuilderForSeason($season, $year);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            24
        );

        return $this->render('anime/by_season.html.twig', [
            'pagination' => $pagination,
            'season' => $season,
            'year' => $year,
        ]);
    }
}