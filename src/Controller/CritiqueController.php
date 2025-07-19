<?php

namespace App\Controller;

use App\Repository\CritiqueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/critiques')]
class CritiqueController extends AbstractController
{
    public function __construct(
        private CritiqueRepository $critiqueRepository
    ) {}

    #[Route('/', name: 'critiques_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        $type = $request->query->get('type', '');
        $sort = $request->query->get('sort', 'date_desc');

        $queryBuilder = $this->critiqueRepository->createQueryBuilderWithFilters($type, $sort);

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('critique/index.html.twig', [
            'pagination' => $pagination,
            'type' => $type,
            'sort' => $sort,
        ]);
    }

    #[Route('/{id}', name: 'critique_show', requirements: ['id' => '\d+'])]
    public function show(int $id): Response
    {
        $critique = $this->critiqueRepository->find($id);

        if (!$critique) {
            throw $this->createNotFoundException('Critique not found');
        }

        return $this->render('critique/show.html.twig', [
            'critique' => $critique,
        ]);
    }
}