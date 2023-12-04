<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\Client;
use App\Entity\Product;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $userPasswordHasher;
    
    public function __construct(UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 20; $i++) {
            $product = new Product;
            $product->setName('Téléphone ' . $i);
            $manager->persist($product);
        }

        $clientList = [];
        for ($i = 0; $i < 10; $i++) {
            $client = new Client;
            $client->setEmail("client" . $i . "@bilemoapi.com");
            $client->setRoles(["ROLE_USER"]);
            $client->setPassword($this->userPasswordHasher->hashPassword($client, "password" . $i));
            $manager->persist($client);

            $clientList[] = $client;
        }

        for ($i = 0; $i < 30; $i++) {
            $user = new User;
            $user->setName('Utilisateur ' . $i);
            $user->setClient($clientList[array_rand($clientList)]);
            $manager->persist($user);
        }

        $manager->flush();
    }
}
