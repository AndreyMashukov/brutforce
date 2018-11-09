<?php

namespace App\Tests\Services;

use App\Exception\BadProxyException;
use App\Services\Brut;
use App\Services\Proxy\ProxyProviderInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

class BrutTest extends KernelTestCase
{
    private const TEST_USER = '';

    private const TEST_PASS = '';

    private const TEST_HOST = '';

    private const TEST_PORT = '22';

    /** @var ProxyProviderInterface */
    private $proxyProvider;

    /** @var LoggerInterface */
    private $logger;

    public function setUp()
    {
        self::$kernel = self::bootKernel();

        $this->proxyProvider = self::$kernel->getContainer()->get('test.proxy_provider');
        $this->logger        = self::$kernel->getContainer()->get('test.logger');
    }

    /**
     * Should allow to try connect to host via SSH.
     *
     * @group functional
     */
    public function testShouldAllowToConnectViaSsh()
    {
        $proxy     = null;
        $brutForce = new Brut($this->proxyProvider, $this->logger);

        $result = false;

        while (true) {
            try {
                $result = $brutForce->connect(self::TEST_USER, self::TEST_PASS, self::TEST_HOST, true, self::TEST_PORT);

                break;
            } catch (BadProxyException $exception) {
                // ignore
            } catch (ProcessTimedOutException $exception) {
                // ignore
            }
        }

        $this->assertTrue($result);
    }
}
