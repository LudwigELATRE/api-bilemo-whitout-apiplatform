<?php

namespace App\DataFixtures;

use App\Entity\Enterprise;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class EnterpriseFixtures extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        for ($i = 0; $i < 10; $i++) {
            $enterprise = new Enterprise();
            $enterprise->setName('Enterprise ' . $i);
            $enterprise->setUuid(Uuid::uuid4()->toString());
            $enterprise->setCreatedAt(new \DateTime());
            $manager->persist($enterprise);
        }
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 1;
    }
}