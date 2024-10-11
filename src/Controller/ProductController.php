<?php

namespace App\Controller;

use App\Repository\EnterpriseRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Annotations as OA;
use Nelmio\ApiDocBundle\Annotation\Model;


/**
 * @param Model $moJustForOptimise
 * @param OA\Info $inJustForOptimize
 */
class ProductController extends AbstractController
{
    private EnterpriseRepository $enterpriseRepository;
    private ProductRepository $productRepository;

    public function __construct(EnterpriseRepository $enterpriseRepository, ProductRepository $productRepository)
    {
        $this->enterpriseRepository = $enterpriseRepository;
        $this->productRepository = $productRepository;
    }
    /**
     * @OA\Get(
     *   tags={"Product"},
     *   path="/api/products/{uuid}",
     *   summary="Get all products",
     *   description="Retrieve a list of all products.",
     *   @OA\Response(
     *     response=200,
     *     description="Returns a list of products.",
     *     @OA\JsonContent(
     *       type="array",
     *       @OA\Items(
     *         @OA\Property(property="id", type="integer"),
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="price", type="float")
     *       )
     *     )
     *   )
     * )
     */
    #[Route('/products/{uuid}', name: 'products', methods: ['GET'])]
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

        return $this->json($data);
    }

    /**
     * @OA\Get(
     *   tags={"Product"},
     *   path="/api/product/{uuid}/{productId}",
     *   summary="Get a specific product by enterprise UUID and product ID",
     *   description="Retrieve a specific product for a given enterprise UUID and product ID.",
     *   @OA\Parameter(
     *     name="uuid",
     *     in="path",
     *     required=true,
     *     description="The UUID of the enterprise.",
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="productId",
     *     in="path",
     *     required=true,
     *     description="The ID of the product.",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Returns a specific product.",
     *     @OA\JsonContent(
     *       @OA\Property(property="id", type="integer"),
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="description", type="string"),
     *       @OA\Property(property="createdAt", type="string", format="date-time"),
     *       @OA\Property(property="updatedAt", type="string", format="date-time"),
     *       @OA\Property(property="available", type="boolean")
     *     )
     *   ),
     *   @OA\Response(
     *     response=404,
     *     description="Product not found."
     *   )
     * )
     */
    #[Route('/product/{uuid}/{productId}', name: 'product', methods: ['GET'])]
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

        return $this->json($data);
    }


    /**
     * @OA\Post(
     *   tags={"Product"},
     *   path="/api/products/enregistrer",
     *   summary="Create a new product",
     *   description="Create a new product.",
     *   @OA\RequestBody(
     *     required=true,
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="price", type="float")
     *     )
     *   ),
     *   @OA\Response(
     *     response=201,
     *     description="Product created successfully.",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="id", type="integer"),
     *       @OA\Property(property="name", type="string"),
     *       @OA\Property(property="price", type="float")
     *     )
     *   )
     * )
     */
    #[Route('/products/enregistrer/', name: 'create_product', methods: ['POST'])]
    public function createProduct(): JsonResponse
    {
        $data = ['id' => 3, 'name' => 'Product 3', 'price' => 300];

        return $this->json($data, 201);
    }
}
