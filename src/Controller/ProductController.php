<?php

namespace App\Controller;

use App\Entity\Product;
use OpenApi\Attributes as OA;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    /**
     * Permet de récupérer la liste des produits.
     */
    #[Route('/api/products', name: 'get_products', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des produits.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class))
        )
    )]
    #[OA\Parameter(
        name: 'page',
        in: 'query',
        description: "La page que l'on veut récupérer",
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Parameter(
        name: 'limit',
        in: 'query',
        description: "Le nombre d'éléments que l'on veut récupérer",
        schema: new OA\Schema(type: 'int')
    )]
    #[OA\Tag(name: 'Products')]
    #[OA\Security(name: 'Bearer')]
    public function getAllProducts(ProductRepository $productRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache, Request $request): JsonResponse
    {
        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);

        $idCache = "getAllProducts" . $page . "-" . $limit;

        $jsonProductList = $cache->get($idCache, function (ItemInterface $item) use ($productRepository, $page, $limit, $serializer) {
            $productList = $productRepository->findAllPaginated($page, $limit);
            if (empty($productList)) {
                throw new HttpException(404);
            }
            $item->tag("productsCache");
            $item->expiresAfter(3600);
            return $serializer->serialize($productList, 'json');
        });
        
        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    /**
     * Permet de récupérer un produit.
     */
    #[Route('/api/products/{id}', name: 'get_product', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne un produit.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Product::class))
        )
    )]
    #[OA\Tag(name: 'Products')]
    #[OA\Security(name: 'Bearer')]
    public function getProduct(Product $product, ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {     
        $jsonProduct = $serializer->serialize($product, 'json');
        
        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }
}
