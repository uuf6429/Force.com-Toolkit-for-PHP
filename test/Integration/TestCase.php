<?php

namespace SForce\Test\Integration;

use SForce\Client\Enterprise;
use SForce\Client\Partner;

class TestCase extends \SForce\Test\TestCase
{
    /**
     * @var string
     */
    protected static $sfUser;

    /**
     * @var string
     */
    protected static $sfPass;

    /**
     * @var string
     */
    protected static $sfToken;

    public static function setUpBeforeClass()
    {
        if ((static::$sfUser = getenv('SALESFORCE_USER')) === false
            || (static::$sfPass = getenv('SALESFORCE_PASS')) === false
            || (static::$sfToken = getenv('SALESFORCE_TOKEN')) === false
        ) {
            self::markTestSkipped(
                'Test requires access to a test SalesForce system, '
                . 'however one of SALESFORCE_USER, SALESFORCE_PASS or SALESFORCE_TOKEN '
                . 'environment variables have not been set.'
            );
        }
    }

    /**
     * @return Partner
     */
    protected function getPartnerClient()
    {
        $client = new Partner();
        $client->createConnection();
        $client->login(static::$sfUser, static::$sfPass . static::$sfToken);

        return $client;
    }

    /**
     * @return Enterprise
     */
    protected function getEnterpriseClient()
    {
        $client = new Enterprise();
        $client->createConnection();
        $client->login(static::$sfUser, static::$sfPass . static::$sfToken);

        return $client;
    }
}
