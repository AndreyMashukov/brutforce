<?php

namespace App\Services\Proxy;

use Guzzle\Http\Client;
use Guzzle\Http\Exception\CurlException;
use Guzzle\Http\Exception\ServerErrorResponseException;

class BestProxiesProvider implements ProxyProviderInterface
{
    private const CACHE_LIST_KEY = 'proxy_provider_list';

    private const LIST_EXPIRE_TIME = 60;

    private const CACHE_BAD_PREFIX = 'bad_proxy_';

    private const CACHE_USED_PREFIX = 'used_proxy_';

    private const CACHE_ITERATOR_PREFIX = 'iterate_proxy_';

    private const USED_EXPIRE_TIME = 6000;

    private const BAD_EXPIRE_TIME = 0;

    /**
     * URL for response proxy data.
     *
     * @var string
     */
    private $url;

    private $client;

    private $memcached;

    public function __construct(\Memcached $memcached, Client $client, string $bestProxiesKey)
    {
        $this->url       = "http://api.best-proxies.ru/proxylist.txt?key={$bestProxiesKey}&limit=0&level=1,2&type=http&response=300";
        $this->client    = $client;
        $this->memcached = $memcached;
    }

    public function getProxy(): string
    {
        $proxy = '';

        while (true) {
            $proxy = $this->getRandomProxy();

            if ($this->isBad($proxy)) {
                continue;
            }

            if ($this->isUsed($proxy)) {
                continue;
            }

            $this->iterateUsage($proxy);
            break;
        }

        return $proxy;
    }

    private function getRandomProxy(): string
    {
        $list = $this->getList();

        return \trim($list[\array_rand($list)]);
    }

    public function getList(): array
    {
        if (false === $list = $this->memcached->get(self::CACHE_LIST_KEY)) {
            $body = '';

            for ($i = 0; $i < 10; ++$i) {
                try {
                    $response = $this->client->get($this->url)->send();
                    $body     = $response->getBody(true);

                    break;
                } catch (ServerErrorResponseException $exception) {
                    $i = 0;

                    continue;
                } catch (CurlException $exception) {
                    \sleep(5);

                    continue;
                }

                return [];
            }

            $list = \explode("\n", $body);

            $this->memcached->set(self::CACHE_LIST_KEY, $list, self::LIST_EXPIRE_TIME);
        }

        return $list;
    }

    public function badProxy(string $proxy)
    {
        $this->memcached->set(self::CACHE_BAD_PREFIX . $proxy, $proxy, self::BAD_EXPIRE_TIME);
    }

    public function iterateUsage(string $proxy): void
    {
        if (false === $iterator = $this->memcached->get(self::CACHE_ITERATOR_PREFIX . $proxy)) {
            $iterator = -1;
        }

        ++$iterator;
        $this->memcached->set(self::CACHE_ITERATOR_PREFIX . $proxy, $iterator, 0);

        if (3 > $iterator) {
            return;
        }

        $this->used($proxy);
    }

    public function used(string $proxy)
    {
        $this->memcached->set(self::CACHE_USED_PREFIX . $proxy, $proxy, self::USED_EXPIRE_TIME);
    }

    public function isBad(string $proxy): bool
    {
        return false !== $this->memcached->get(self::CACHE_BAD_PREFIX . $proxy);
    }

    public function isUsed(string $proxy): bool
    {
        return false !== $this->memcached->get(self::CACHE_USED_PREFIX . $proxy);
    }
}
