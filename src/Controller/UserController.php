<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Knp\Component\Pager\PaginatorInterface;

class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    #[Route('/users', name: 'users_index')]
    public function index(Request $request, PaginatorInterface $paginator): Response
    {
        // TODO: Implement user listing when user system is properly configured
        return $this->render('user/index.html.twig', [
            'pagination' => null,
        ]);
    }

    #[Route('/user/profile', name: 'user_profile')]
    #[IsGranted('ROLE_USER')]
    public function profile(): Response
    {
        return $this->render('user/profile.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/user/anime-list', name: 'user_anime_list')]
    #[IsGranted('ROLE_USER')]
    public function animeList(): Response
    {
        return $this->render('user/anime_list.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    #[Route('/user/manga-list', name: 'user_manga_list')]
    #[IsGranted('ROLE_USER')]
    public function mangaList(): Response
    {
        return $this->render('user/manga_list.html.twig', [
            'user' => $this->getUser(),
        ]);
    }
}