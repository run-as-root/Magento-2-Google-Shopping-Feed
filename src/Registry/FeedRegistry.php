<?php

declare(strict_types=1);

namespace RunAsRoot\GoogleShoppingFeed\Registry;

class FeedRegistry
{
    private array $cache = [];
    private array $cachePerStore = [];

    /**
     * @param string $key
     * @param mixed $data
     * @return void
     */
    public function register(string $key, $data): void
    {
        $this->cache[$key] = $data;
    }

    /**
     * @param string $key
     * @param int $storeId
     * @param mixed $data
     * @return void
     */
    public function registerForStore(string $key, int $storeId, $data): void
    {
        $this->cachePerStore[$storeId][$key] = $data;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function registry(string $key)
    {
        return $this->cache[$key] ?? null;
    }

    /**
     * @param string $key
     * @param int $storeId
     * @return mixed|null
     */
    public function registryForStore(string $key, int $storeId)
    {
        return $this->cachePerStore[$storeId][$key] ?? null;
    }

    public function clean(): void
    {
        $this->cache = [];
    }

    public function cleanForStore(int $storeId): void
    {
        if (!isset($this->cachePerStore[$storeId])) {
            return;
        }

        unset($this->cachePerStore[$storeId]);
    }
}
