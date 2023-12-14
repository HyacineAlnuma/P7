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
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    /**
     * Permet de récupérer la liste des utilisateurs liés à un client.
     */
    #[Route('/api/users/client/{id}', name: 'get_users', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne la liste des utilisateurs liés à un client.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['getUsers']))
        )
    )]
    #[OA\Tag(name: 'Users')]
    #[OA\Security(name: 'Bearer')]
    public function getAllClientsUsers(int $id, UserRepository $userRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $idCache = "getClientsUsers" . $id;

        $jsonUserList = $cache->get($idCache, function (ItemInterface $item) use ($userRepository, $id, $serializer) {
            echo("L'élément n'est pas encore en cache \n");
            $item->tag("usersCache");
            $context = SerializationContext::create()->setGroups(['getUsers']);
            $userList = $userRepository->findBy(['client' => $id]);
            return $serializer->serialize($userList, 'json', $context);
        });
    
        return new JsonResponse($jsonUserList, Response::HTTP_OK, [], true);
    }

    /**
     * Permet de récupérer un utilisateur.
     */
    #[Route('/api/users/{id}', name: 'get_user', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Retourne un utilisateurs lié à un client.',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: User::class, groups: ['getUsers']))
        )
    )]
    #[OA\Tag(name: 'Users')]
    #[OA\Security(name: 'Bearer')]
    public function getOneClientsUser(int $id, UserRepository $userRepository, SerializerInterface $serializer, TagAwareCacheInterface $cache): JsonResponse
    {
        $user = $userRepository->findBy(['id' => $id]);

        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUser = $serializer->serialize($user, 'json', $context);
        
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }


    #[Route('/api/users', name: 'create_user', methods: ['POST'])]
    // #[OA\Response(
    //     response: 200,
    //     description: 'Crée un utilisateur.'
    // )]
    // #[OA\RequestBody(
    //     required: true
    // )]
    // #[OA\Tag(name: 'Users')]
    // #[OA\Security(name: 'Bearer')]
    public function createUser(Request $request, EntityManagerInterface $em, SerializerInterface $serializer, ClientRepository $clientRepository, ValidatorInterface $validator, TagAwareCacheInterface $cache): JsonResponse
    {
        $user = $serializer->deserialize($request->getContent(), User::class, 'json');

        $errors = $validator->validate($user);
        if ($errors->count() > 0) {
            return new JsonResponse($serializer->serialize($errors, 'json'), JsonResponse::HTTP_BAD_REQUEST, [], true);
        }

        $content = $request->toArray();
        $idClient = $content['idClient'] ?? -1;
        $user->setClient($clientRepository->find($idClient));

        $cache->invalidateTags(["usersCache"]);
        $em->persist($user);
        $em->flush();

        $context = SerializationContext::create()->setGroups(['getUsers']);
        $jsonUser = $serializer->serialize($user, 'json', $context);
        
        return new JsonResponse($jsonUser, Response::HTTP_OK, [], true);
    }

    /**
     * Permet de supprimer un utilisateur.
     */
    #[Route('/api/users/{id}', name: 'delete_user', methods: ['DELETE'])]
    #[OA\Response(
        response: 200,
        description: 'Supprime un utilisateur.'
    )]
    #[OA\Tag(name: 'Users')]
    #[OA\Security(name: 'Bearer')]
    public function deleteUser(User $user, EntityManagerInterface $em, TagAwareCacheInterface $cache): JsonResponse
    {
        $cache->invalidateTags(["usersCache"]);
        $em->remove($user);
        $em->flush();
        
        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
