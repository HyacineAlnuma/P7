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
        $this->loadProducts($manager);
        $this->loadClientsAndUsers($manager);

        $manager->flush();
    }

    private function loadProducts(ObjectManager $manager) {
        $capacities = ['64', '128', '256'];
        for ($i = 0; $i < 20; $i++) {
            $product = new Product;
            $product->setName('Téléphone ' . $i);
            $randomCap = array_rand($capacities, 1);
            $product->setCapacity($capacities[$randomCap] . 'go');
            $product->setWeight(rand(150, 210) . 'g');
            $product->setScreenSize((rand(60, 70) / 10) . ' inches');
            $manager->persist($product);
        }
    }

    private function loadClientsAndUsers(ObjectManager $manager) {
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
            $user->setEmail("user" . $i . "@bilemoapi.com");
            $user->setClient($clientList[array_rand($clientList)]);
            $manager->persist($user);
        }
    }
}
