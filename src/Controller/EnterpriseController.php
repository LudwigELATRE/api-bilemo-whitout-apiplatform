<?php

namespace App\Controller;

use App\Entity\Enterprise;
use App\Repository\EnterpriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Controller\SwaggerUiController as SWG;
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
    /**
     * @SWG\Tag(name="Enterprise")
     */
    /**
     * @OA\Get(
     *   tags={"Enterprise"},
     *   path="/api/enterprise/{uuid}",
     *   summary="Get enterprise with uuid",
     *   description="Retrieve an enterprise by its uuid.",
     *   @OA\Response(
     *     response=200,
     *     description="Returns the enterprise details.",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer"),
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="uuid", type="string")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Enterprise not found"
     *   )
     * )
     */

    #[Route('/enterprise/{uuid}', name: 'enterprises', requirements: ["uuid" => Requirement::UUID_V4], methods: ['GET'])]
    public function getEnterprises(string $uuid): JsonResponse
    {
        $enterprise = $this->enterpriseRepository->findOneBy(['uuid' => $uuid]);

        return $this->json($enterprise, 200, [], ['groups' => 'enterprise']);
    }

    /**
     * @OA\Post(
     *   tags={"Enterprise"},
     *   path="/api/enterprise",
     *   summary="Create a new enterprise",
     *   description="Create a new enterprise.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       required={"name"},
     *       @OA\Property(property="name", type="string", description="The name of the enterprise")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Enterprise created successfully.",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer"),
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="uuid", type="string"),
     *     )
     *   )
     * )
     */
    #[Route('/enterprise', name: 'create_enterprise', methods: ['POST'])]
    public function createEnterprise(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name'])) {
            return $this->json(['message' => 'Missing required field: name'], 400);
        }

        $enterprise = new Enterprise();
        $enterprise->setName($data['name']);
        $enterprise->setUuid(Uuid::uuid4()->toString());
        $enterprise->setCreatedAt(new \DateTime());

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
