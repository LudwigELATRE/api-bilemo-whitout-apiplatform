<?php

namespace App\Controller;

use App\Entity\Enterprise;
use App\Repository\EnterpriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\Routing\Requirement\Requirement;

class EnterpriseController extends AbstractController
{
    private EnterpriseRepository $enterpriseRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(EnterpriseRepository $enterpriseRepository, EntityManagerInterface $entityManager)
    {
        $this->enterpriseRepository = $enterpriseRepository;
        $this->entityManager = $entityManager;

    }


    #[Route('/enterprise/{uuid}', name: 'get_enterprise', requirements: ['uuid' => Requirement::UUID_V4], methods: ['GET'])]
    #[OA\Get(
        description: "Retrieve an enterprise by its UUID.",
        summary: "Get enterprise by UUID"
    )]
    #[OA\Response(
        response: 200,
        description: "Returns the enterprise details.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "id", type: "integer"),
                new OA\Property(property: "name", type: "string"),
                new OA\Property(property: "uuid", type: "string")
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Enterprise not found."
    )]
    #[OA\Tag(name: "Enterprise")]
    public function getEnterprises(string $uuid): JsonResponse
    {
        $enterprise = $this->enterpriseRepository->findOneBy(['uuid' => $uuid]);

        return $this->json($enterprise, 200, [], ['groups' => 'enterprise']);
    }

    #[Route('/api/enterprise', name: 'create_enterprise', methods: ['POST'])]
    #[OA\Post(
        description: "Create a new enterprise.",
        summary: "Create a new enterprise"
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ["name"],
            properties: [
                new OA\Property(property: "name", description: "The name of the enterprise", type: "string"),
                new OA\Property(property: "password", description: "The password of the enterprise", type: "string")
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Enterprise created successfully.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "name", type: "string"),
                new OA\Property(property: "uuid", type: "string")
            ],
            type: "object"
        )
    )]
    #[OA\Tag(name: "Enterprise")]
    #[Security(name: "Bearer")]
    public function createEnterprise(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo 'Erreur de décodage JSON : ' . $e->getMessage();
        }

        if (!isset($data['name'],$data['password'])) {
            return $this->json(['message' => 'Missing required field: name'], 400);
        }

        $enterprise = new Enterprise();
        $enterprise->setName($data['name']);
        $enterprise->setPassword($data['password']);
        $enterprise->setUuid(Uuid::uuid4()->toString());
        $enterprise->setCreatedAt(new \DateTime());
        $enterprise->setRoles(["ROLE_ENTERPRISE"]);

        $this->entityManager->persist($enterprise);
        $this->entityManager->flush();

        $responseData = [
            'id' => $enterprise->getId(),
            'name' => $enterprise->getName(),
            'uuid' => $enterprise->getUuid(),
        ];

        return $this->json($responseData, 201);
    }


}
