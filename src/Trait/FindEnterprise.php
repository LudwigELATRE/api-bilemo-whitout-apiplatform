<?php

namespace App\Trait;

use App\Entity\Enterprise;
use App\Repository\EnterpriseRepository;

trait FindEnterprise
{
    public function findEnterpriseById(EnterpriseRepository $enterpriseRepository, string $uuid): ?Enterprise
    {
        return $enterpriseRepository->findOneBy(['uuid' => $uuid]);
    }
}