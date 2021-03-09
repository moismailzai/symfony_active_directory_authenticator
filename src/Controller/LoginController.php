<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


class LoginController extends AbstractController
{
    #[Route(path: '/login', name: 'login', schemes: ['https'])]
    public function login(
        AuthenticationUtils $authenticationUtils
    ): Response {
        if ($user = $this->getUser()) {
            return $this->redirectToRoute('homepage');
        }
        return $this->render(
            'login.html.twig',
            [
                'error' => $authenticationUtils->getLastAuthenticationError(),
                'last_username' => $authenticationUtils->getLastUsername(),
            ]
        );
    }

    #[Route(path: '/logout', name: 'logout', schemes: ['https'])]
    public function logout(): void
    {
    }
}