<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EnterpriseRepository;
use App\Repository\UserRepository;
use App\Service\CacheService;
use App\Service\TokenUtils;
use App\Trait\FindEnterprise;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;


/**
 * @param Model $moJustForOptimise
 * @param OA\Info $inJustForOptimize
 */
class UserController extends AbstractController
{
    use FindEnterprise;

    public $cachename = "user";

    public function __construct(
        private readonly EnterpriseRepository        $enterpriseRepository,
        private readonly UserRepository              $userRepository,
        private readonly EntityManagerInterface      $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly CacheService                $cacheService,
        private readonly TokenUtils                  $tokenUtils
    )
    {
    }


    #[Route('/users', name: 'users', methods: ['GET'])]
    #[OA\Get(
        description: "Retrieve a list of all users for a specific enterprise.",
        summary: "Get all users"
    )]
    #[OA\Parameter(
        name: "page",
        description: "The page number (default: 1).",
        in: "query",
        required: false,
        schema: new OA\Schema(type: "integer", default: 1)
    )]
    #[OA\Parameter(
        name: "limit",
        description: "The number of items per page (default: 10).",
        in: "query",
        required: false,
        schema: new OA\Schema(type: "integer", default: 5)
    )]
    #[OA\Response(
        response: 200,
        description: "Returns a list of users.",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "integer"),
                    new OA\Property(property: "username", type: "string"),
                    new OA\Property(property: "email", type: "string"),
                ]
            )
        )
    )]
    #[OA\Tag(name: "User")]
    public function getUsers(Request $request): JsonResponse
    {
        try {
            $uuid = $this->tokenUtils->getUuidFromToken($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        $enterprise = $this->findEnterpriseById($this->enterpriseRepository, $uuid);

        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', 5);

        $totalProducts = $this->userRepository->countProductsByEnterpriseId($enterprise->getId());
        $users = $this->userRepository->findAllUsersByEnterpriseId($enterprise->getId(), $page, $limit);

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

        $totalPages = ceil($totalProducts / $limit);

        $cache = $this->cacheService->getCache($this->cachename, $data);

        return $this->json([
            'products' => $cache,
            'pagination' => [
                'total' => $totalProducts,
                'page' => $page,
                'limit' => $limit,
                'totalPages' => $totalPages,
            ],
        ]);
    }

    #[Route('/user/{id}', name: 'user', methods: ['GET'])]
    #[OA\Get(
        description: "Retrieve a specific user for a given enterprise UUID and user ID.",
        summary: "Get a specific user by enterprise UUID and user ID"
    )]
    #[OA\Parameter(
        name: "id",
        description: "The ID of the user.",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Returns a specific user.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "id", type: "integer"),
                new OA\Property(property: "email", type: "string"),
                new OA\Property(property: "firstname", type: "string"),
                new OA\Property(property: "lastname", type: "string"),
                new OA\Property(property: "date_of_birth", type: "string", format: "date"),
                new OA\Property(property: "available", type: "boolean"),
            ]
        )
    )]
    #[OA\Response(response: 404, description: "User not found.")]
    #[OA\Tag(name: "User")]
    public function getUserById(int $id, Request $request): JsonResponse
    {
        try {
            $uuid = $this->tokenUtils->getUuidFromToken($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        $enterprise = $this->findEnterpriseById($this->enterpriseRepository, $uuid);

        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        $user = $this->userRepository->findOneBy([
            'id' => $id,
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


    #[Route('/user', name: 'create_user', methods: ['POST'])]
    #[OA\Post(
        description: "Create a new user associated with an enterprise.",
        summary: "Create a new user"
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["firstname", "lastname", "email", "uuid", "password"],
            properties: [
                new OA\Property(property: "firstname", description: "First name of the user", type: "string"),
                new OA\Property(property: "lastname", description: "Last name of the user", type: "string"),
                new OA\Property(property: "email", description: "Email of the user", type: "string"),
                new OA\Property(property: "password", description: "Password of the user", type: "string"),
                new OA\Property(property: "available", description: "Availability status of the user", type: "boolean"),
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 201,
        description: "User created successfully.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "id", description: "User ID", type: "integer"),
                new OA\Property(property: "firstname", description: "First name of the user", type: "string"),
                new OA\Property(property: "lastname", description: "Last name of the user", type: "string"),
                new OA\Property(property: "email", description: "Email of the user", type: "string"),
            ],
            type: "object"
        )
    )]
    #[OA\Tag(name: "User")]
    public function createUser(Request $request): JsonResponse
    {
        try {
            $uuid = $this->tokenUtils->getUuidFromToken($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        $enterprise = $this->findEnterpriseById($this->enterpriseRepository, $uuid); // Injection du repository

        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo 'Erreur de décodage JSON : ' . $e->getMessage();
        }

        if (!isset($data['firstname'], $data['email'], $data['password'])) {
            return $this->json(['error' => 'Missing required fields.'], 400);
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

        $this->cacheService->clearCache($this->cachename);

        return $this->json([
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'date_of_birth' => $user->getDateOfBirth(),
            'available' => $user->isAvailable(),
        ], 201);
    }


    #[Route('/user/{id}', name: 'update_user', methods: ['PUT'])]
    #[OA\Put(
        description: "Update the details of an existing user by enterprise UUID and user ID.",
        summary: "Update a user"
    )]
    #[OA\Parameter(
        name: "id",
        description: "The ID of the user",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "firstname", type: "string"),
                new OA\Property(property: "lastname", type: "string"),
                new OA\Property(property: "email", type: "string"),
                new OA\Property(property: "available", type: "boolean"),
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "User updated successfully.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "id", type: "integer"),
                new OA\Property(property: "firstname", type: "string"),
                new OA\Property(property: "lastname", type: "string"),
                new OA\Property(property: "email", type: "string"),
            ]
        )
    )]
    #[OA\Response(response: 404, description: "User or enterprise not found.")]
    #[OA\Tag(name: "User")]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        try {
            $uuid = $this->tokenUtils->getUuidFromToken($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        $enterprise = $this->findEnterpriseById($this->enterpriseRepository, $uuid);

        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        $user = $this->userRepository->findOneBy([
            'id' => $id,
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

        $this->cacheService->clearCache($this->cachename);

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


    #[Route('/user/{id}', name: 'delete_user', methods: ['DELETE'])]
    #[OA\Delete(
        description: "Delete an existing user by enterprise UUID and user ID.",
        summary: "Delete a user"
    )]
    #[OA\Parameter(
        name: "id",
        description: "The ID of the user",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(response: 204, description: "User deleted successfully.")]
    #[OA\Response(response: 404, description: "User or enterprise not found.")]
    #[OA\Tag(name: "User")]
    public function deleteUser(int $id, Request $request): JsonResponse
    {
        try {
            $uuid = $this->tokenUtils->getUuidFromToken($request);
        } catch (\InvalidArgumentException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 401);
        }

        $enterprise = $this->findEnterpriseById($this->enterpriseRepository, $uuid);

        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        $user = $this->userRepository->findOneBy([
            'id' => $id,
            'enterprise' => $enterprise->getId(),
        ]);

        if (!$user) {
            return $this->json(['error' => 'User not found.'], 404);
        }

        $this->entityManager->remove($user);
        $this->entityManager->flush();

        $this->cacheService->clearCache($this->cachename);

        return $this->json(['message' => 'User deleted successfully.'], 200);
    }
}
