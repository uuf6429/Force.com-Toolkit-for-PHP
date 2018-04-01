<?php

namespace SForce\Test\Unit;

use SForce\ProxySettings;
use SForce\Test\TestCase;

class ProxySettingsTest extends TestCase
{
    public function testToArrayWorksAsExpected()
    {
        $settings = new ProxySettings();
        $settings->host = 'host';
        $settings->port = 1234;
        $settings->login = 'user';
        $settings->password = 'pass';

        $this->assertEquals(
            $settings->toArray(),
            [
                'proxy_host' => 'host',
                'proxy_port' => 1234,
                'proxy_login' => 'user',
                'proxy_password' => 'pass',
            ]
        );
    }
}
