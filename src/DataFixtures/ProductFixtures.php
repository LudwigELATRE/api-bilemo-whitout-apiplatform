<?php

namespace App\DataFixtures;

use App\Entity\Product;
use App\Repository\EnterpriseRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ProductFixtures extends Fixture implements OrderedFixtureInterface
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
            $product = new Product();
            $product->setName('Product ' . $i);
            $product->setDescription('Description ' . $i);
            $product->setCreatedAt(new \DateTimeImmutable());
            $product->setUpdatedAt(new \DateTimeImmutable());
            $product->setAvailable(true);
            $product->setEnterprise($enterprises[random_int(0, 9)]);
            $manager->persist($product);
        }

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 3;
    }
}