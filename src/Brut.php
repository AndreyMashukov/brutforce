<?php

namespace BrutForce;

use Symfony\Component\Process\Process;

class Brut
{
    /**
     * @see {https://linux.die.net/man/1/sshpass}
     *
     * @param string      $user
     * @param string      $pass
     * @param string      $host
     * @param null|string $proxy
     * @param string      $port
     *
     * @return bool
     */
    public function connect(string $user, string $pass, string $host, ?string $proxy = null, string $port = '22'): bool
    {
        $command = "/usr/bin/sshpass -p {$pass} ssh {$user}@{$host} -p{$port} 'echo \$USER'";

        if ($proxy) {
            $command .= " -o \"ProxyCommand = nc - X connect - x {$proxy} %h %p\"";
        }

        $process = new Process($command);
        $process->start();

        foreach ($process as $type => $data) {
            if ($process::OUT !== $type) {
                continue;
            }

            if ($user !== \trim($data)) {
                continue;
            }

            $process->stop();

            return true;
        }

        $process->stop();

        return false;
    }
}
