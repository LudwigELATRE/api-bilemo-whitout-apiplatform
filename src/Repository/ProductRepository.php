<?php

namespace App\Repository;

use App\Entity\Enterprise;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    public function findAllProductsByEnterpriseId(int $enterpriseId, int $page = 1, int $limit = 10): array
    {
        $offset = ($page - 1) * $limit;

        return $this->createQueryBuilder('p')
            ->andWhere('p.enterprise = :enterpriseId')
            ->setParameter('enterpriseId', $enterpriseId)
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countProductsByEnterpriseId(int $enterpriseId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.enterprise = :enterpriseId')
            ->setParameter('enterpriseId', $enterpriseId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
