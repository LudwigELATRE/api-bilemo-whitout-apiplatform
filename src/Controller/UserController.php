<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EnterpriseRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;


/**
 * @param Model $moJustForOptimise
 * @param OA\Info $inJustForOptimize
 */
class UserController extends AbstractController
{
    private EnterpriseRepository $enterpriseRepository;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(EnterpriseRepository $enterpriseRepository, UserRepository $userRepository, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->enterpriseRepository = $enterpriseRepository;
        $this->userRepository = $userRepository;
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
    }
    /**
     * @OA\Get(
     *   tags={"User"},
     *   path="/api/users/{uuid}",
     *   summary="Get all users",
     *   description="Retrieve a list of all users.",
     *   @OA\Response(
     *     response=200,
     *     description="Returns a list of users.",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(
     *         @OA\Property(property="id", type="integer"),
     *         @OA\Property(property="username", type="string"),
     *         @OA\Property(property="email", type="string")
     *       )
     *     )
     *   )
     * )
     */
    #[Route('/users/{uuid}', name: 'users', methods: ['GET'])]
    public function getUsers(string $uuid): JsonResponse
    {
        $enterprise = $this->enterpriseRepository->findOneBy(['uuid' => $uuid]);

        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        $users = $this->userRepository->findAllUsersByEnterpriseId($enterprise->getId());

        $data = [];
        foreach ($users as $user) {
            $data[] = [
                'id' => $user->getId(),
                'email' => $user->getEmail(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'date_of_birth' => $user->getDateOfBirth(),
                'available' => $user->isAvailable(),
            ];
        }

        return $this->json($data);
    }

    /**
     * @OA\Get(
     *   tags={"User"},
     *   path="/api/user/{uuid}/{userId}",
     *   summary="Get a specific user by enterprise UUID and user ID",
     *   description="Retrieve a specific user for a given enterprise UUID and user ID.",
     *   @OA\Parameter(
     *     name="uuid",
     *     in="path",
     *     required=true,
     *     description="The UUID of the enterprise.",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="userId",
     *     in="path",
     *     required=true,
     *     description="The ID of the user.",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Returns a specific user.",
     *     @OA\JsonContent(
     *       @OA\Property(property="id", type="integer"),
     *       @OA\Property(property="email", type="string"),
     *       @OA\Property(property="firstname", type="string"),
     *       @OA\Property(property="lastname", type="string"),
     *       @OA\Property(property="date_of_birth", type="string", format="date"),
     *       @OA\Property(property="available", type="boolean")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="User not found."
     *   )
     * )
     */
    #[Route('/user/{uuid}/{userId}', name: 'user', methods: ['GET'])]
    public function getUserById(string $uuid, int $userId): JsonResponse
    {
        $enterprise = $this->enterpriseRepository->findOneBy(['uuid' => $uuid]);

        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        $user = $this->userRepository->findOneBy([
            'id' => $userId,
            'enterprise' => $enterprise->getId(),
        ]);

        if (!$user) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        $data = [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'date_of_birth' => $user->getDateOfBirth(),
            'available' => $user->isAvailable(),
        ];

        return $this->json($data);
    }


    /**
     * @OA\Post(
     *   tags={"User"},
     *   path="/api/users",
     *   summary="Create a new user",
     *   description="Create a new user.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"firstname", "lastname", "email", "uuid", "password"}, // Champs requis
     *       @OA\Property(property="uuid", type="string", description="UUID of the enterprise"),
     *       @OA\Property(property="firstname", type="string", description="First name of the user"),
     *       @OA\Property(property="lastname", type="string", description="Last name of the user"),
     *       @OA\Property(property="email", type="string", description="Email of the user"),
     *       @OA\Property(property="password", type="string", description="Password of the user"),
     *       @OA\Property(property="available", type="boolean", description="Availability status of the user")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="User created successfully.",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer", description="User ID"),
     *       @OA\Property(property="firstname", type="string", description="First name of the user"),
     *       @OA\Property(property="lastname", type="string", description="Last name of the user"),
     *       @OA\Property(property="email", type="string", description="Email of the user")
     *     )
     *   )
     * )
     */
    #[Route('/users', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['firstname'], $data['email'], $data['uuid'])) {
            return $this->json(['error' => 'Missing required fields.'], 400);
        }

        $enterprise = $this->enterpriseRepository->findOneBy(['uuid' => $data['uuid']]);
        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        $user = new User();
        $user->setLastname($data['lastname']);
        $user->setFirstname($data['firstname']);
        $user->setEmail($data['email']);
        $user->setAvailable($data['available']);
        $user->setDateOfBirth(new \DateTime());
        $user->setRoles(['ROLE_USER']);
        $user->setEnterprise($enterprise);
        $password = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($password);

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json([
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'date_of_birth' => $user->getDateOfBirth(),
            'available' => $user->isAvailable(),
        ], 201);
    }


    /**
     * @OA\Put(
     *   tags={"User"},
     *   path="/api/user/{uuid}/{userId}",
     *   summary="Update a user",
     *   description="Update the details of an existing user by enterprise UUID and user ID.",
     *   @OA\Parameter(
     *     name="uuid",
     *     in="path",
     *     required=true,
     *     description="The UUID of the enterprise.",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="userId",
     *     in="path",
     *     required=true,
     *     description="The ID of the user",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="firstname", type="string"),
     *       @OA\Property(property="lastname", type="string"),
     *       @OA\Property(property="email", type="string")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="User updated successfully.",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer"),
     *       @OA\Property(property="firstname", type="string"),
     *       @OA\Property(property="lastname", type="string"),
     *       @OA\Property(property="email", type="string")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="User or enterprise not found."
     *   )
     * )
     */
    #[Route('/user/{uuid}/{userId}', name: 'update_user', methods: ['PUT'])]
    public function updateUser(string $uuid, int $userId, Request $request): JsonResponse
    {
        $enterprise = $this->enterpriseRepository->findOneBy(['uuid' => $uuid]);

        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        $user = $this->userRepository->findOneBy([
            'id' => $userId,
            'enterprise' => $enterprise->getId(),
        ]);

        if (!$user) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['firstname'])) {
            $user->setFirstname($data['firstname']);
        }
        if (isset($data['lastname'])) {
            $user->setLastname($data['lastname']);
        }
        if (isset($data['email'])) {
            $user->setEmail($data['email']);
        }
        if (isset($data['available'])) {
            $user->setAvailable($data['available']);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $updatedData = [
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'date_of_birth' => $user->getDateOfBirth(),
            'available' => $user->isAvailable(),
        ];

        return $this->json($updatedData, 200);
    }


    /**
     * @OA\Delete(
     *   tags={"User"},
     *   path="/api/user/{uuid}/{userId}",
     *   summary="Delete a user",
     *   description="Delete an existing user by enterprise UUID and user ID.",
     *   @OA\Parameter(
     *     name="uuid",
     *     in="path",
     *     required=true,
     *     description="The UUID of the enterprise.",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="userId",
     *     in="path",
     *     required=true,
     *     description="The ID of the user",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=204,
     *     description="User deleted successfully."
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="User or enterprise not found."
     *   )
     * )
     */
    #[Route('/user/{uuid}/{userId}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(string $uuid, int $userId): JsonResponse
    {
        $enterprise = $this->enterpriseRepository->findOneBy(['uuid' => $uuid]);

        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        $user = $this->userRepository->findOneBy([
            'id' => $userId,
            'enterprise' => $enterprise->getId(),
        ]);

        if (!$user) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        return $this->json(['message' => 'User deleted successfully.'], 200);
    }
}
