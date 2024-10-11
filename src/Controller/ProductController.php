<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\EnterpriseRepository;
use App\Repository\ProductRepository;
use App\Service\CacheService;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;
use Nelmio\ApiDocBundle\Annotation\Model;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Security;


/**
 * @param Model $moJustForOptimise
 * @param OA\Info $inJustForOptimize
 */
class ProductController extends AbstractController
{
    public function __construct(
        private readonly EnterpriseRepository $enterpriseRepository,
        private readonly ProductRepository    $productRepository,
        private readonly CacheService         $cacheService,
        private readonly EntityManagerInterface $entityManager
    )
    {
    }


    #[Route('/api/products/{uuid}', name: 'products', methods: ['GET'])]
    #[OA\Get(
        description: "Retrieve a list of all products.",
        summary: "Get all products"
    )]
    #[OA\Response(
        response: 200,
        description: "Returns a list of products.",
        content: new OA\JsonContent(
            type: "array",
            items: new OA\Items(
                properties: [
                    new OA\Property(property: "id", type: "integer"),
                    new OA\Property(property: "name", type: "string"),
                    new OA\Property(property: "price", type: "float"),
                ]
            )
        )
    )]
    #[OA\Tag(name: "Product")]
    public function getProducts(string $uuid): JsonResponse
    {
        $enterprise = $this->enterpriseRepository->findOneBy(['uuid' => $uuid]);

        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        $products = $this->productRepository->findAllProductsByEnterpriseId($enterprise->getId());

        $data = [];
        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'Description' => $product->getDescription(),
                'createdAt' => $product->getCreatedAt(),
                'updatedAt' => $product->getUpdatedAt(),
                'available' => $product->IsAvailable(),
            ];
        }

        $cache = $this->cacheService->getCache($uuid, $data);

        return $this->json($cache);
    }

    #[Route('/api/product/{uuid}/{productId}', name: 'product', methods: ['GET'])]
    #[OA\Get(
        description: "Retrieve a specific product for a given enterprise UUID and product ID.",
        summary: "Get a specific product by enterprise UUID and product ID"
    )]
    #[OA\Parameter(
        name: "uuid",
        description: "The UUID of the enterprise.",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "string")
    )]
    #[OA\Parameter(
        name: "productId",
        description: "The ID of the product.",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
    )]
    #[OA\Response(
        response: 200,
        description: "Returns a specific product.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "id", type: "integer"),
                new OA\Property(property: "name", type: "string"),
                new OA\Property(property: "description", type: "string"),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "updatedAt", type: "string", format: "date-time"),
                new OA\Property(property: "available", type: "boolean")
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: "Product not found."
    )]
    #[OA\Tag(name: "Product")]
    public function getProduct(string $uuid, int $productId): JsonResponse
    {
        $enterprise = $this->enterpriseRepository->findOneBy(['uuid' => $uuid]);
        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        $product = $this->productRepository->findOneBy([
            'id' => $productId,
            'enterprise' => $enterprise->getId(),
        ]);

        if (!$product) {
            return $this->json(['error' => 'Product not found.'], 404);
        }

        $data = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'createdAt' => $product->getCreatedAt(),
            'updatedAt' => $product->getUpdatedAt(),
            'available' => $product->isAvailable(),
        ];

        $cache = $this->cacheService->getCache($uuid, $data);

        return $this->json($cache);
    }


    #[Route('/api/products/enregistrer/{uuid}', name: 'create_product', methods: ['POST'])]
    #[OA\Post(
        description: "Create a new product.",
        summary: "Create a new product"
    )]
    #[OA\Parameter(
        name: "uuid",
        description: "The UUID of the enterprise.",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "string")
    )]
    #[OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "name", type: "string"),
                new OA\Property(property: "description", type: "string"),
            ],
            type: "object"
        )
    )]
    #[OA\Response(
        response: 201,
        description: "Product created successfully.",
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "id", type: "integer"),
                new OA\Property(property: "name", type: "string"),
                new OA\Property(property: "description", type: "string"),
                new OA\Property(property: "createdAt", type: "string", format: "date-time"),
                new OA\Property(property: "available", type: "boolean")
            ],
            type: "object"
        )
    )]
    #[OA\Tag(name: "Product")]
    public function createProduct(Request $request,string $uuid): JsonResponse
    {
        $enterprise = $this->enterpriseRepository->findOneBy(['uuid' => $uuid]);
        if (!$enterprise) {
            return $this->json(['error' => 'Enterprise not found.'], 404);
        }

        try {
            $data = json_decode($request->getContent(), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            echo 'Erreur de décodage JSON : ' . $e->getMessage();
        }

        if (!isset($data['name'])) {
            return $this->json(['error' => 'Missing required fields.'], 400);
        }

        $product = new Product();
        $product->setName($data['name']);
        $product->setDescription($data['description']);
        $product->setAvailable(true);
        $product->setCreatedAt(new \DateTime());
        $product->setEnterprise($enterprise);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->cacheService->clearCache($uuid);

        $responseData = [
            'id' => $product->getId(),
            'name' => $product->getName(),
            'description' => $product->getDescription(),
            'createdAt' => $product->getCreatedAt(),
            'available' => $product->isAvailable(),
            ];


        return $this->json($responseData, 201);
    }
}
