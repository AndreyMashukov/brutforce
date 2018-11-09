<?php

namespace Tests\BrutForce;

use BrutForce\Brut;
use PHPUnit\Framework\TestCase;

class BrutTest extends TestCase
{
    private const TEST_USER = '';

    private const TEST_PASS = '';

    private const TEST_HOST = '';

    private const TEST_PORT = '';

    /**
     * Should allow to try connect to host via SSH.
     *
     * @group functional
     */
    public function testShouldAllowToConnectViaSsh()
    {
        $brutForce = new Brut();
        $result    = $brutForce->connect(self::TEST_USER, self::TEST_PASS, self::TEST_HOST, null, self::TEST_PORT);

        $this->assertTrue($result);
    }
}
