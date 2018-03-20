<?php

namespace SForce\Test\Integration\Partner;

use SForce\SchemaGen;
use SForce\Test\Integration\TestCase;

class SchemaGenTest extends TestCase
{
    const OUT_FILE = __DIR__ . '/schema.sql';

    protected function setUp()
    {
        parent::setUp();

        if (file_exists(self::OUT_FILE)) {
            unlink(self::OUT_FILE);
        }
    }

    protected function tearDown()
    {
        if (file_exists(self::OUT_FILE)) {
            unlink(self::OUT_FILE);
        }

        parent::tearDown();
    }

    public function testSchemaGeneration()
    {
        $gen = new SchemaGen($this->getPartnerClient(), self::OUT_FILE);
        $gen->generate();

        $this->assertFileExists(self::OUT_FILE);
    }
}
