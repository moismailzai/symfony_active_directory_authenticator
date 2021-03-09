<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomePageController extends AbstractController
{
    #[Route(path: '/', name: 'homepage', schemes: ['https'])]
    public function index(): Response
    {
        if (!$user = $this->getUser()) {
            return $this->redirectToRoute('login');
        }
        return $this->render(
            'home.html.twig',
            [
                'user' => $user,
            ]
        );
    }
}