<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/UserController.php',
        ]);
    }
}

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    public function createUser(
        Request $request,
        ValidatorInterface $validator,
        EntityManagerInterface $entityManager,
        UserPasswordEncoderInterface $passwordEncoder
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setEmail($data['email']);
        $user->setPassword($passwordEncoder->encodePassword($user, $data['password']));
        $user->setFirstName($data['firstName']);
        $user->setLastName($data['lastName']);

        $errors = $validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse((string) $errors, JsonResponse::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['status' => 'User created!'], JsonResponse::HTTP_CREATED);
    }
}

public function updateUser(int $id, Request $request, UserRepository $userRepository, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
{
    $user = $userRepository->find($id);
    if (!$user) {
        return new JsonResponse(['status' => 'User not found!'], JsonResponse::HTTP_NOT_FOUND);
    }

    $data = json_decode($request->getContent(), true);
    $user->setFirstName($data['firstName'] ?? $user->getFirstName());
    $user->setLastName($data['lastName'] ?? $user->getLastName());

    $errors = $validator->validate($user);
    if (count($errors) > 0) {
        return new JsonResponse((string) $errors, JsonResponse::HTTP_BAD_REQUEST);
    }

    $em->persist($user);
    $em->flush();

    return new JsonResponse(['status' => 'User updated!']);
}

security:
    encoders:
        App\Entity\User:
            algorithm: bcrypt

    providers:
        app_user_provider:
            entity:
                class: App\Entity\User
                property: email

    firewalls:
        login:
            pattern: ^/api/login
            stateless: true
            json_login:
                check_path: /api/login
                username_path: email
                password_path: password

public function getUser(int $id, UserRepository $userRepository): JsonResponse
{
    $user = $userRepository->find($id);

    if (!$user) {
        return new JsonResponse(['status' => 'User not found!'], JsonResponse::HTTP_NOT_FOUND);
    }

    $data = [
        'id' => $user->getId(),
        'email' => $user->getEmail(),
        'firstName' => $user->getFirstName(),
        'lastName' => $user->getLastName(),
    ];

    return new JsonResponse($data);
}

