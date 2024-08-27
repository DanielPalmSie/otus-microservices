<?php

namespace App\Controller;


use App\Manager\UserManager;
use App\Service\AuthService;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/auth/v1/token')]
class TokenController extends AbstractController
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly JWTEncoderInterface $jwtEncoder,
        private readonly UserManager $userManager,
        private readonly LoggerInterface $logger,
    ) {}

    #[Route(path: '', methods: ['POST'])]
    public function getTokenAction(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        $username = $data['username'] ?? null;
        $password = $data['password'] ?? null;

        if (!$username || !$password) {
            return new JsonResponse(['message' => 'Authorization requireds'], Response::HTTP_UNAUTHORIZED);
        }
        if (!$this->authService->isCredentialsValid($username, $password)) {
            return new JsonResponse(['message' => 'Invalid password or username'], Response::HTTP_FORBIDDEN);
        }

        return new JsonResponse(['token' => $this->authService->getToken($username)]);
    }

    #[Route(path: '/validate', methods: ['GET'])]
    public function validateTokenAction(Request $request): Response
    {
        $headers = $request->headers->all();
        foreach ($headers as $key => $value) {
            $this->logger->debug("Header $key: " . implode(',', $value));
        }

        $this->logger->info('Starting token validation process.');

        $authorizationHeader = $request->headers->get('Authorization');
        $this->logger->debug('Authorization header:', ['Authorization' => $authorizationHeader]);

        if (!$authorizationHeader || strpos($authorizationHeader, 'Bearer ') !== 0) {
            $this->logger->warning('Token not provided or improperly formatted.');
            return new JsonResponse(['message' => 'Token not provided'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            $token = substr($authorizationHeader, 7);
            $this->logger->debug('Token extracted from header.', ['token' => $token]);

            $tokenData = $this->jwtEncoder->decode($token);
            $this->logger->debug('Decoded token data:', ['tokenData' => $tokenData]);

            if (!$tokenData || !isset($tokenData['username'])) {
                $this->logger->warning('Invalid token data or username missing.');
                return new JsonResponse(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
            }

            $user = $this->userManager->findUserByLogin($tokenData['username']);
            $this->logger->debug('User found:', ['user' => $user]);

            if (!$user) {
                $this->logger->warning('User not found for provided token.');
                return new JsonResponse(['message' => 'Invalid user'], Response::HTTP_UNAUTHORIZED);
            }

            $this->logger->info('Token validation successful.');
            return new Response(null, Response::HTTP_OK);
        } catch (\Exception $e) {
            $this->logger->error('Token validation failed.', ['exception' => $e]);
            return new JsonResponse(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }
    }

}