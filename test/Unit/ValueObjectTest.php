<?php

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
