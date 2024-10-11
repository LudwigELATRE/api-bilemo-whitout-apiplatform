<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Repository\EnterpriseRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture implements OrderedFixtureInterface
{
    private EnterpriseRepository $enterpriseRepository;

    public function __construct(EnterpriseRepository $enterpriseRepository)
    {
        $this->enterpriseRepository = $enterpriseRepository;
    }

    public function load(ObjectManager $manager): void
    {
        $enterprises = $this->enterpriseRepository->findAll();
        for ($i = 0; $i < 30; $i++) {
            $user = new User();
            $user->setFirstname('firstname ' . $i);
            $user->setLastname('lastname ' . $i);
            $user->setEmail('user' . $i . '@bilemo.com');
            $user->setPassword('password');
            $user->setRoles(['ROLE_USER']);
            $user->setAvailable(true);
            $user->setEnterprise($enterprises[random_int(0, 9)]);
            $user->setDateOfBirth(new \DateTime());
            $manager->persist($user);
        }

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 2;
    }
}
