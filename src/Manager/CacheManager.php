<?php

declare(strict_types=1);

namespace WcMipConnector\Manager;

defined('ABSPATH') || exit;

use WcMipConnector\Repository\CacheRepository;

class CacheManager
{
    /** @var CacheRepository */
    private $repository;

    public function __construct()
    {
        $this->repository = new CacheRepository();
    }

    public function findOneById(string $itemId, string $ttlInterval): ?string
    {
        return $this->repository->findOneById($itemId, $ttlInterval);
    }

    public function set(string $itemId, string $itemData, string $namespace, int $ttlSeconds = 900): void
    {
        $data = [
            'item_id' => $itemId,
            'item_data' => $itemData,
            'item_expiration_timestamp' => time() + $ttlSeconds,
            'namespace' => $namespace,
        ];

        $this->repository->set($data);
    }

    public function prune(int $limit): void
    {
        $this->repository->prune($limit);
    }
}