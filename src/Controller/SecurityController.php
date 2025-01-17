<?php

namespace App\Controller;

use App\Entity\Enterprise;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use OpenApi\Attributes as OA;


class SecurityController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface   $entityManager,
        private readonly JWTTokenManagerInterface $jwtManager)
    {
    }

    #[Route('/login/login_check', methods: ['POST'])]
    #[OA\Post(
        description: "Generate a JWT token after providing valid user credentials.",
        summary: "Generate a JWT token"
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name", "password"],
            properties: [
                new OA\Property(property: "name", type: "string", example: "name"),
                new OA\Property(property: "password", type: "string", example: "password")
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 200,
        description: "JWT Token generated successfully.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "token", type: "string", example: "JWT_TOKEN")
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 401,
        description: "Invalid credentials."
    )]
    #[OA\Tag(name: "Authentication")]
    public function loginCheck(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo 'Erreur de décodage JSON : ' . $e->getMessage();
        }

        if (!isset($data['name'],$data['password'])) {
            return new JsonResponse(['message' => 'Missing required fields: name or password'], 400);
        }

        $enterprise = $this->entityManager->getRepository(Enterprise::class)
            ->findOneBy(['name' => $data['name'], 'password' => $data['password']]);

        if (!$enterprise) {
            throw new AuthenticationException('Invalid credentials.');
        }

        $token = $this->jwtManager->create($enterprise);

        return new JsonResponse(['token' => $token], 200);
    }
}
