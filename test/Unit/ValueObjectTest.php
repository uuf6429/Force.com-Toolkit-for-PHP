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
use SForce\ValueObject;

class ValueObjectTest extends TestCase
{
    public function testPropertiesInBaseClass()
    {
        $testObject = new ValueObjectTestClassA();
        $this->assertEquals(
            [
                'untypedProperty' => null,
                'stringProperty' => null,
            ],
            $testObject->toArray()
        );
    }

    public function testPropertiesInChildClass()
    {
        $testObject = new ValueObjectTestClassC(
            [
                'untypedProperty' => 'stuff',
                'stringProperty' => 'more stuff',
                'mixedPropWithNs' => null,
            ]
        );
        $this->assertEquals(
            [
                'untypedProperty' => 'stuff',
                'stringProperty' => 'more stuff',
                'mixedPropWithNs' => null,
            ],
            $testObject->toArray()
        );
    }

    public function testPropertiesCanBeSetFromArray()
    {
        $testObject = new ValueObjectTestClassA();
        $testObject->fromArray(
            [
                'untypedProperty' => 'some stuff',
                'stringProperty' => 'some more stuff',
            ]
        );
        $this->assertEquals(
            [
                'untypedProperty' => 'some stuff',
                'stringProperty' => 'some more stuff',
            ],
            $testObject->toArray()
        );
    }

    public function testFieldsCannotBeMissing()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data parameter is missing one or more fields: untypedProperty.');

        new ValueObjectTestClassA(
            [
                // 'untypedProperty' is intentionally left out
                'stringProperty' => null,
            ]
        );
    }

    public function testObjectNotAllowedAsDataParameter()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Data parameter should be an array.');

        new ValueObjectTestClassA((object)[]);
    }

    public function testPropertyIsReadable()
    {
        $testObject = new ValueObjectTestClassA(
            [
                'untypedProperty' => 123,
                'stringProperty' => 'more stuff',
            ]
        );
        $this->assertSame(123, $testObject->untypedProperty);
        $this->assertSame('more stuff', $testObject->stringProperty);
    }

    public function testPropertyIsVisible()
    {
        $testObject = new ValueObjectTestClassA(
            [
                'untypedProperty' => 123,
                'stringProperty' => 'more stuff',
            ]
        );
        $this->assertTrue(isset($testObject->untypedProperty));
        $this->assertTrue(isset($testObject->stringProperty));
    }
}

/**
 * @property $untypedProperty An untyped property.
 * @property string $stringProperty
 */
class ValueObjectTestClassA extends ValueObject
{
}

class ValueObjectTestClassB extends ValueObjectTestClassA
{
}

/**
 * @property \SForce\SObject|null $mixedPropWithNs
 */
class ValueObjectTestClassC extends ValueObjectTestClassB
{
}
