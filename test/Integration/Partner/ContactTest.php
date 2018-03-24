<?php

/*
 * Copyright (c) 2007, salesforce.com, inc.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are permitted provided
 * that the following conditions are met:
 *
 *    Redistributions of source code must retain the above copyright notice, this list of conditions and the
 *    following disclaimer.
 *
 *    Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *    the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *    Neither the name of salesforce.com, inc. nor the names of its contributors may be used to endorse or
 *    promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED
 * WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A
 * PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR
 * ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED
 * TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 * NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

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
