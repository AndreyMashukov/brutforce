<?php

namespace App\Services\Proxy;

interface ProxyProviderInterface
{
    public function getProxy(): string;

    public function iterateUsage(string $proxy): void;

    public function getList(): array;

    public function used(string $proxy);

    public function badProxy(string $proxy);

    public function isUsed(string $proxy): bool;

    public function isBad(string $proxy): bool;
}
