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

namespace SForce;

use SForce\Exception\UnsupportedFieldTypeException;

/**
 * This class generates simplified DDL schema for a given SF connection. The generated
 * DDL is particularly useful when developing SOQL queries; you can configure your IDE
 * to use the generated SQL file for hinting (eg; "DDL Data Source" in PhpStorm).
 */
class SchemaGen
{
    /**
     * @var Client\Base
     */
    private $sfClient;

    /**
     * @var string
     */
    private $output;

    /**
     * @var string Default content for empty ENUMs (since empty enum is not valid sql).
     */
    protected static $EMPTY_ENUM = "'#empty#'";

    /**
     * @var string Default content for empty SETs (since empty set is not valid sql).
     */
    protected static $EMPTY_SET = "'#empty#'";

    /**
     * @param Client\Base $sfClient SalesForce client.
     * @param string $output Output destination path.
     */
    public function __construct(Client\Base $sfClient, $output)
    {
        $this->sfClient = $sfClient;
        $this->output = $output;
    }

    public function generate()
    {
        $globalDesc = $this->sfClient->describeGlobal();

        $fh = fopen($this->output, 'wb');
        try {
            foreach ($globalDesc->sobjects as $globalEntityDesc) {
                $entity = $this->sfClient->describeSObject($globalEntityDesc->name);
                fwrite($fh, PHP_EOL . $this->generateTableDDL($entity) . PHP_EOL);
            }
        } finally {
            fclose($fh);
        }
    }

    /**
     * @param \stdClass $entity
     *
     * @return string
     */
    protected function generateTableDDL($entity)
    {
        static $prefix = ',' . PHP_EOL . '    ';

        return sprintf(
            'CREATE TABLE %s (%s    %s%s);',
            $entity->name,
            PHP_EOL,
            implode(
                $prefix,
                array_map(
                    [$this, 'generateFieldDDL'],
                    $entity->fields
                )
            ),
            PHP_EOL
        );
    }

    /**
     * @param \stdClass $field
     *
     * @return string
     *
     * @throws UnsupportedFieldTypeException
     */
    protected function generateFieldDDL($field)
    {
        $allowNull = $field->nillable ? 'NULL' : 'NOT NULL';
        $varchar = 'VARCHAR(' . ($field->length ?: 'MAX') . ')';
        $decimal = "DECIMAL({$field->precision}, {$field->scale})";
        $integer = "INT({$field->digits})";

        switch ($field->type) {
            case 'id':
                return "$field->name $varchar PRIMARY KEY";
            case 'boolean':
                return "$field->name BIT $allowNull";
            case 'url':
            case 'phone':
            case 'email':
            case 'string':
            case 'textarea':
            case 'combobox': // mix between picklist and open text
                return "$field->name $varchar $allowNull";
            case 'picklist':
                if (!isset($field->picklistValues) || !$field->picklistValues) {
                    return "$field->name ENUM(" . static::$EMPTY_ENUM . ") $allowNull";
                }

                return "$field->name ENUM('"
                       . implode(
                           "', '",
                           array_map(
                               function ($pickListValue) {
                                   return $pickListValue->value;
                               },
                               $field->picklistValues
                           )
                       ) . "') $allowNull";
            case 'multipicklist':
                if (!isset($field->picklistValues) || !$field->picklistValues) {
                    return "$field->name SET(" . static::$EMPTY_SET . ") $allowNull";
                }

                return "$field->name SET('"
                       . implode(
                           "', '",
                           array_map(
                               function ($pickListValue) {
                                   return $pickListValue->value;
                               },
                               $field->picklistValues
                           )
                       ) . "') $allowNull";
            case 'int':
                return "$field->name $integer $allowNull";
            case 'double':
            case 'percent':
            case 'currency':
                return "$field->name $decimal $allowNull";
            case 'date':
            case 'time':
            case 'datetime':
                return "$field->name " . strtoupper($field->type) . " $allowNull";
            case 'reference':
                return "$field->name $varchar $allowNull"; // TODO foreign key to referenceTo[0]..
            case 'base64':
                return "$field->name TEXT $allowNull";
            case 'anyType':
                return "$field->name TEXT $allowNull";

            default:
                throw new UnsupportedFieldTypeException($field->type);
        }
    }
}
