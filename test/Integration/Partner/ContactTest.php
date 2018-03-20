<?php

namespace SForce\Test\Integration\Partner;

use SForce\Test\Integration\TestCase;

class ContactTest extends TestCase
{
    public function testGetContacts()
    {
        $expected = [
            [
                'Id' => '0011r00001mcVPLAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'GenePoint',
                ],
            ],
            [
                'Id' => '0011r00001mcVPJAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'United Oil & Gas, UK',
                ],
            ],
            [
                'Id' => '0011r00001mcVPKAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'United Oil & Gas, Singapore',
                ],
            ],
            [
                'Id' => '0011r00001mcVPBAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'Edge Communications',
                ],
            ],
            [
                'Id' => '0011r00001mcVPCAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'Burlington Textiles Corp of America',
                ],
            ],
            [
                'Id' => '0011r00001mcVPDAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'Pyramid Construction Inc.',
                ],
            ],
            [
                'Id' => '0011r00001mcVPEAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'Dickenson plc',
                ],
            ],
            [
                'Id' => '0011r00001mcVPFAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'Grand Hotels & Resorts Ltd',
                ],
            ],
            [
                'Id' => '0011r00001mcVPHAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'Express Logistics and Transport',
                ],
            ],
            [
                'Id' => '0011r00001mcVPIAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'University of Arizona',
                ],
            ],
            [
                'Id' => '0011r00001mcVPGAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'United Oil & Gas Corp.',
                ],
            ],
            [
                'Id' => '0011r00001mcVPMAA2',
                'type' => 'Account',
                'fields' => (object) [
                    'Name' => 'sForce',
                ],
            ],
        ];

        $actual = array_map(
            'get_object_vars',
            iterator_to_array(
                $this->getPartnerClient()
                    ->query('SELECT Id, Name FROM Account')
            )
        );

        $this->assertEquals($expected, $actual);
    }
}
