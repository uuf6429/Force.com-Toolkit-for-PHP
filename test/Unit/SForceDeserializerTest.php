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

namespace SForce\Test\Unit;

use SForce\Test\TestCase;
use JMS\Serializer;
use SForce\Wsdl;

class SForceDeserializerTest extends TestCase
{
    /**
     * @var Serializer\Serializer
     */
    private $serializer;

    public function setUp()
    {
        $this->serializer = Serializer\SerializerBuilder::create()
            ->addDefaultDeserializationVisitors()
            ->setPropertyNamingStrategy(new Serializer\Naming\IdenticalPropertyNamingStrategy())
            ->setDeserializationVisitor(
                'object',
                new Serializer\ObjectDeserializationVisitor(
                    new Serializer\Naming\IdenticalPropertyNamingStrategy()
                )
            )
            ->setAnnotationReader(new Serializer\Annotation\PhpDocReader(new Serializer\SForceTypeResolver()))
            ->build();
    }

    /**
     * @param object $data
     * @param string $class
     * @param null|object $expectedResult
     * @param null|\Exception $expectedException
     *
     * @dataProvider deserializationScenarioDataProvider
     */
    public function testDeserializationScenario($data, $class, $expectedResult, $expectedException = null)
    {
        if ($expectedException) {
            $this->expectException(get_class($expectedException));
            $this->expectExceptionMessage($expectedException->getMessage());
        }

        $this->assertEquals($expectedResult, $this->serializer->deserialize($data, $class, 'object'));
    }

    /**
     * @return array
     *
     * @throws \ReflectionException
     */
    public function deserializationScenarioDataProvider()
    {
        return [
            'deserialize objects with object properties' => [
                '$data' => (object)[
                    'metadataServerUrl' => 'http://metadata/',
                    'passwordExpired' => false,
                    'sandbox' => true,
                    'serverUrl' => 'http://server/',
                    'sessionId' => 'SID12456',
                    'userId' => 'U1245632',
                    'userInfo' => (object)[
                        'accessibilityMode' => true,
                        'orgDisallowHtmlAttachments' => false,
                        'orgHasPersonAccounts' => true,
                        'organizationId' => 'OID23',
                        'organizationMultiCurrency' => false,
                        'organizationName' => 'Umbrella, Inc.',
                        'profileId' => 'PID809432',
                        'userEmail' => 'bc@umi.com',
                        'userFullName' => 'B. Collins',
                        'userId' => 'UID876532',
                        'userLanguage' => 'DE',
                        'userLocale' => 'DE',
                        'userName' => 'bcollins',
                        'userTimeZone' => '0100',
                        'userType' => 'sys',
                        'userUiSkin' => 'none',
                    ],
                ],
                '$class' => Wsdl\LoginResult::class,
                '$expectedResult' => (new Wsdl\LoginResult(null, null))
                    ->setMetadataServerUrl('http://metadata/')
                    ->setPasswordExpired(false)
                    ->setSandbox(true)
                    ->setServerUrl('http://server/')
                    ->setSessionId('SID12456')
                    ->setUserId('U1245632')
                    ->setUserInfo(
                        new Wsdl\GetUserInfoResult(
                            true,
                            false,
                            true,
                            'OID23',
                            false,
                            'Umbrella, Inc.',
                            'PID809432',
                            'bc@umi.com',
                            'B. Collins',
                            'UID876532',
                            'DE',
                            'DE',
                            'bcollins',
                            '0100',
                            'sys',
                            'none'
                        )
                    ),
            ],

            'test non-objects not allowed' => [
                '$data' => 1234,
                '$class' => \stdClass::class,
                '$expectedResult' => null,
                '$expectedException' => new Serializer\Exception\RuntimeException('An object graph was expected.')
            ],

            'test array of simple types and enums' => [
                '$data' => (object)[
                    'autoNumber' => true,
                    'byteLength' => 4,
                    'calculated' => false,
                    'caseSensitive' => true,
                    'createable' => false,
                    'custom' => false,
                    'defaultedOnCreate' => true,
                    'deprecatedAndHidden' => false,
                    'digits' => 4,
                    'filterable' => true,
                    'groupable' => false,
                    'idLookup' => true,
                    'label' => 'Some Field',
                    'length' => 8,
                    'name' => 'SomeField',
                    'nameField' => false,
                    'nillable' => true,
                    'precision' => 4,
                    'restrictedPicklist' => false,
                    'scale' => 0,
                    'soapType' => Wsdl\soapType::tnsID,
                    'type' => Wsdl\fieldType::reference,
                    'unique' => true,
                    'updateable' => false,
                ],
                '$class' => Wsdl\Field::class,
                '$expectedResult' => new Wsdl\Field(
                    true,
                    4,
                    false,
                    true,
                    false,
                    false,
                    true,
                    false,
                    4,
                    true,
                    false,
                    true,
                    'Some Field',
                    8,
                    'SomeField',
                    false,
                    true,
                    4,
                    false,
                    0,
                    'tns:ID',
                    'reference',
                    true,
                    false
                ),
            ],

            'test object with basic PHP class' => [
                '$data' => (object)[
                    'CreatedDate' => '2018-02-20T18:50:00+01:00',
                ],
                '$class' => Wsdl\CaseTeamTemplate::class,
                '$expectedResult' => (new \ReflectionClass(Wsdl\CaseTeamTemplate::class))
                    ->newInstanceWithoutConstructor()
                    ->setCreatedDate(new \DateTime('20-02-2018 18:50')),
            ],

            'test object with a property using a non-existent type' => [
                '$data' => (object)[],
                '$class' => SForceDeserializerTestClass::class,
                '$expectedResult' => null,
                '$expectedException' => new Serializer\Exception\RuntimeException(
                    'Type \'SomeFakeType\' is not supported or known.'
                ),
            ],
        ];
    }
}
