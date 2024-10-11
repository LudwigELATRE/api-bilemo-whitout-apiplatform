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
     *   path="/api/users/{id}",
     *   summary="Update a user",
     *   description="Update the details of an existing user.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="The ID of the user",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="username", type="string"),
     *       @OA\Property(property="email", type="string")
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="User updated successfully.",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer"),
     *       @OA\Property(property="username", type="string"),
     *       @OA\Property(property="email", type="string")
     *     )
     *   )
     * )
     */
    #[Route('/{id}', name: 'update_user', methods: ['PUT'])]
    public function updateUser(int $id): JsonResponse
    {
        $data = ['id' => $id, 'username' => 'updatedUser', 'email' => 'updated@example.com'];

        return $this->json($data);
    }

    /**
     * @OA\Delete(
     *   tags={"User"},
     *   path="/api/users/{id}",
     *   summary="Delete a user",
     *   description="Delete an existing user.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="The ID of the user",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=204,
     *     description="User deleted successfully."
     *   )
     * )
     */
    #[Route('/{id}', name: 'delete_user', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        return $this->json(null, 204);
    }
}
