<?php

namespace App\Controller;

use App\Entity\Manga;
use App\Repository\MangaRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/mangas')]
class MangaController extends AbstractController
{
    public function __construct(
        private MangaRepository $mangaRepository
    ) {}

    #[Route('/', name: 'manga_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $auteur = $request->query->get('auteur', '');
        $year = $request->query->get('year', '');
        $letter = $request->query->get('letter', '');
        $sort = $request->query->get('sort', 'date_desc');

        $queryBuilder = $this->mangaRepository->createQueryBuilderWithFilters($search, $auteur, $year, $sort);
        
        // Apply letter filter
        if ($letter) {
            if ($letter === '#') {
                $queryBuilder->andWhere('m.titre LIKE :num0 OR m.titre LIKE :num1 OR m.titre LIKE :num2 OR m.titre LIKE :num3 OR m.titre LIKE :num4 OR m.titre LIKE :num5 OR m.titre LIKE :num6 OR m.titre LIKE :num7 OR m.titre LIKE :num8 OR m.titre LIKE :num9')
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
                $queryBuilder->andWhere('m.titre LIKE :letter')
                           ->setParameter('letter', $letter . '%');
            }
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            24,
            [
                'defaultSortFieldName' => 'm.dateAjout',
                'defaultSortDirection' => 'desc',
                'sortFieldParameterName' => 'sort_field',
                'sortDirectionParameterName' => 'sort_direction'
            ]
        );

        return $this->render('manga/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search,
            'auteur' => $auteur,
            'year' => $year,
            'letter' => $letter,
            'sort' => $sort,
            'authors' => $this->mangaRepository->getAllAuthors(),
            'years' => $this->mangaRepository->getAllYears(),
        ]);
    }

    #[Route('/top', name: 'manga_top')]
    public function top(Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->mangaRepository->createQueryBuilderForTopRated();

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            50
        );

        return $this->render('manga/top.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/auteur/{auteur}', name: 'manga_by_author')]
    public function byAuthor(string $auteur, Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->mangaRepository->createQueryBuilderForAuthor($auteur);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            24
        );

        return $this->render('manga/by_author.html.twig', [
            'pagination' => $pagination,
            'auteur' => $auteur,
        ]);
    }

    #[Route('/year/{year}', name: 'manga_by_year', requirements: ['year' => '\d{4}'])]
    public function byYear(int $year, Request $request, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $this->mangaRepository->createQueryBuilderForYear($year);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            24
        );

        return $this->render('manga/by_year.html.twig', [
            'pagination' => $pagination,
            'year' => $year,
        ]);
    }

    #[Route('/{slug}', name: 'manga_show', requirements: ['slug' => '[a-z0-9\-]+'])]
    public function show(string $slug): Response
    {
        $manga = $this->mangaRepository->findBySlug($slug);

        if (!$manga) {
            throw $this->createNotFoundException('Manga not found');
        }

        return $this->render('manga/show.html.twig', [
            'manga' => $manga,
        ]);
    }
}