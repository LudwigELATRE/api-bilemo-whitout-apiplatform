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

    /**
     * @OA\Delete(
     *   tags={"Product"},
     *   path="/api/products/{id}",
     *   summary="Delete a product",
     *   description="Delete an existing product.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     required=true,
     *     description="The ID of the product",
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=204,
     *     description="Product deleted successfully."
     *   )
     * )
     */
    #[Route('/{id}', name: 'delete_product', methods: ['DELETE'])]
    public function deleteProduct(int $id): JsonResponse
    {
        return $this->json(null, 204);
    }
}
