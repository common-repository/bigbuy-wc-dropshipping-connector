<?php

declare(strict_types=1);

namespace WcMipConnector\Service;

use WcMipConnector\Client\Base\Exception\ClientErrorException;
use WcMipConnector\Enum\StatusTypes;
use WcMipConnector\Service\CacheService as BaseCacheService;

class ShippingCostConcurrentLockService
{
    public const CACHE_KEY_BIGBUY_REQUEST = 'bigbuy_request';

    public const INCREMENT_SECONDS = 1;

    public const RETRY_CACHE_EXPIRATION = 15;

    public const MAX_RETRIES = 10;

    /** @var ShippingCostConcurrentLockService */
    public static $instance;

    public static function getInstance(): ShippingCostConcurrentLockService
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function execute(): void
    {
        $cacheContent = BaseCacheService::getInstance()->findOneById(self::CACHE_KEY_BIGBUY_REQUEST, self::RETRY_CACHE_EXPIRATION . ' SECOND');
        $retryCount = 0;
        $currentTimestamp = \time();
        $actualTtl = $currentTimestamp;
        $ttl = $currentTimestamp;

        if ($cacheContent) {
            $cacheData = \json_decode($cacheContent, true);

            $retryCount = $cacheData['retry_count'] ?? 0;
            $actualTtl = $cacheData['expiration_timestamp'] ?? $currentTimestamp;
        }

        if ($retryCount > self::MAX_RETRIES && $currentTimestamp >= $actualTtl) {
            $retryCount = 0;
        }

        if ($retryCount < self::MAX_RETRIES && $currentTimestamp < $actualTtl) {
            $ttl = $actualTtl;
        }

        if ($retryCount < self::MAX_RETRIES && $currentTimestamp >= $actualTtl) {
            $ttl = $currentTimestamp + $retryCount + self::INCREMENT_SECONDS;
        }

        BaseCacheService::getInstance()->save(self::CACHE_KEY_BIGBUY_REQUEST,
            '{"retry_count": '.($retryCount + 1).', "expiration_timestamp":'.$ttl.'}',
            'main',
            $retryCount + self::INCREMENT_SECONDS
        );

        $sleepTime = ($retryCount + self::INCREMENT_SECONDS) * 1000000;
        $sleepTime = \random_int($sleepTime + 500000, $sleepTime + 1500000);

        \usleep($sleepTime);
    }

    /**
     * @throws ClientErrorException
     */
    public function check(): void
    {
        $cacheContent = BaseCacheService::getInstance()->findOneById(self::CACHE_KEY_BIGBUY_REQUEST, self::RETRY_CACHE_EXPIRATION . ' SECOND');

        if (!$cacheContent) {
            return;
        }

        $currentTimestamp = \time();
        $cacheData = \json_decode($cacheContent, true);

        $actualTtl = $cacheData['expiration_timestamp'] ?? $currentTimestamp;

        if ($currentTimestamp >= $actualTtl) {
            return;
        }

        throw new ClientErrorException(StatusTypes::HTTP_TOO_MANY_REQUESTS, 'Consecutive 429');
    }
}