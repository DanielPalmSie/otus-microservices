<?php

namespace App\Manager;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserManager
{
    public function __construct(
        private readonly EntityManagerInterface      $entityManager,
    ){}

    public function findUserByLogin(string $login): ?User
    {
        /** @var UserRepository $userRepository */
        $userRepository = $this->entityManager->getRepository(User::class);
        /** @var User|null $user */
        $user = $userRepository->findOneBy(['name' => $login]);

        return $user;
    }
}