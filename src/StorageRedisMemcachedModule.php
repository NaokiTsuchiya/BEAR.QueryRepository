<?php

declare(strict_types=1);

namespace BEAR\QueryRepository;

use BEAR\RepositoryModule\Annotation\EtagPool;
use Memcached;
use Psr\Cache\CacheItemPoolInterface;
use Ray\Di\AbstractModule;
use Ray\PsrCacheModule\Annotation\CacheNamespace;
use Ray\PsrCacheModule\Annotation\MemcacheConfig;
use Ray\PsrCacheModule\MemcachedProvider;
use Ray\PsrCacheModule\Psr6RedisModule;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

use function array_map;
use function explode;

/**
 * Bind redis to shared storage, Bind memcache to etag pool
 */
final class StorageRedisMemcachedModule extends AbstractModule
{
    /** @var string */
    private $redisServer;

    /** @var list<list<string>> */
    private $memcacheServer;

    /**
     * @param string $redisServer 'localhost:6379' {host}:{port}
     */
    public function __construct(string $redisServer, string $memcacheServer, ?AbstractModule $module = null)
    {
        $this->redisServer = $redisServer;
        $this->memcacheServer = array_map(static function ($memcacheServer) {
            return explode(':', $memcacheServer);
        }, explode(',', $memcacheServer));
        parent::__construct($module);
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->install(new Psr6RedisModule($this->redisServer));
        $this->bind(CacheItemPoolInterface::class)->annotatedWith(EtagPool::class)->toConstructor(MemcachedAdapter::class, [
            'namespace' => CacheNamespace::class,
        ]);
        $this->bind()->annotatedWith(MemcacheConfig::class)->toInstance($this->memcacheServer);
        $this->bind(Memcached::class)->toProvider(MemcachedProvider::class);
    }
}
