<?php

namespace App\Service;

use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class CacheService
{
    public function __construct(
        private TagAwareCacheInterface $cache,
    )
    {
    }

    public function getCache(string $cacheName, $data): array
    {
        $idCache = $cacheName;
        return $this->cache->get($idCache, function (ItemInterface $item) use ($data) {
            $item->tag('cache');
            return $data;
        });
    }

    public function clearCache(string $cacheName): void
    {
        $this->cache->delete($cacheName);
    }
}