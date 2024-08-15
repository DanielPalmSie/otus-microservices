<?php

namespace App\Controller;

use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/api/v1/token')]
class TokenController extends AbstractController
{
    public function __construct(private readonly AuthService $authService)
    {}

    #[Route(path: '', methods: ['POST'])]
    public function getTokenAction(Request $request): Response
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');


        if (!$username || !$password) {
            return new JsonResponse(['message' => 'Authorization required'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$this->authService->isCredentialsValid($username, $password)) {
            return new JsonResponse(['message' => 'Invalid password or username'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(['token' => $this->authService->getToken($username)]);
    }
}