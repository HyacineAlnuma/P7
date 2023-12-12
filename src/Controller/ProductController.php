<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use JMS\Serializer\SerializerInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Route('/api/products', name: 'get_products', methods: ['GET'])]
    public function getAllProducts(ProductRepository $productRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getAllProducts";

        $jsonProductList = $cache->get($idCache, function (ItemInterface $item) use ($productRepository, $serializer) {
            echo("L'élément n'est pas encore en cache \n");
            $item->tag("productsCache");
            $item->expiresAfter(3600);
            $productList = $productRepository->findAll();
            return $serializer->serialize($productList, 'json');
        });
        
        return new JsonResponse($jsonProductList, Response::HTTP_OK, [], true);
    }

    #[Route('/api/product/{id}', name: 'get_product', methods: ['GET'])]
    public function getProduct(Product $product, ProductRepository $productRepository, SerializerInterface $serializer): JsonResponse
    {     
        $jsonProduct = $serializer->serialize($product, 'json');
        
        return new JsonResponse($jsonProduct, Response::HTTP_OK, [], true);
    }
}
