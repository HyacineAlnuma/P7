<?php

namespace App\Controller;

use App\Entity\User;
use OpenApi\Attributes as OA;
use App\Repository\UserRepository;
use App\Repository\ClientRepository;
use JMS\Serializer\SerializerInterface;
use Doctrine\ORM\EntityManagerInterface;
use JMS\Serializer\SerializationContext;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Contracts\Cache\ItemInterface;
use Nelmio\ApiDocBundle\Annotation\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    /**
     * Permet de récupérer la liste des utilisateurs liés à un client.
     */
    #[Route('/api/users', name: 'get_users', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des utilisateurs liés à un client.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['getUsers']))
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
    #[OA\Tag(name: 'Users')]
    #[OA\Security(name: 'Bearer')]
    public function getAllClientsUsers(UserRepository $userRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache, Request $request): JsonResponse
    {
        $id = $this->getUser()->getId();

        $page = $request->get('page', 1);
        $limit = $request->get('limit', 5);

        $idCache = "getClientsUsers" . $id . "page" . $page . "limit" . $limit;

        $jsonUserList = $cache->get($idCache, function (ItemInterface $item) use ($userRepository, $id, $page, $limit, $serializer) {
            $userList = $userRepository->findClientsUsersPaginated($id, $page, $limit);
            if (empty($userList)) {
                throw new HttpException(404);
            }
            $item->tag("usersCache");
            $context = SerializationContext::create()->setGroups(['getUsers']);
            return $serializer->serialize($userList, 'json', $context);
        });
    
        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    /**
     * Permet de récupérer un utilisateur lié à un client.
     */
    #[Route('/api/users/{id}', name: 'get_user', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne un utilisateurs lié à un client.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['getUser']))
        )
    )]
    #[OA\Tag(name: 'Users')]
    #[OA\Security(name: 'Bearer')]
    #[IsGranted('view', 'user', 'Access denied')]
    public function getOneClientsUser(User $user, UserRepository $userRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $context = SerializationContext::create()->setGroups(['getUser']);
        $jsonUser = $serializer->serialize($user, 'json', $context);
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
     * Permet de créer un utilisateur.
     */
    #[Route('/api/users', name: 'create_user', methods: ['POST'])]
    public function createUser(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, ClientRepository $clientRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        try {
            $user = $serializer->deserialize($request->getContent(), User::class, 'json');
        } catch (\Exception $e) {
            throw new HttpException(400);
        }

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $user->setClient($this->getUser());

        $cache->invalidateTags(["usersCache"]);
        $em->persist($user);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getUser']);
        $jsonUser = $serializer->serialize($user, 'json', $context);
        
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
     * Permet de supprimer un utilisateur lié à un client.
     */
    #[Route('/api/users/{id}', name: 'delete_user', methods: ['DELETE'])]
    #[OA\Response(
        response: 200,
        description: 'Supprime un utilisateur lié à un client.'
    )]
    #[OA\Tag(name: 'Users')]
    #[OA\Security(name: 'Bearer')]
    #[IsGranted('delete', 'user', 'Access denied')]
    public function deleteUser(User $user, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(["usersCache"]);
        $em->remove($user);
        $em->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
