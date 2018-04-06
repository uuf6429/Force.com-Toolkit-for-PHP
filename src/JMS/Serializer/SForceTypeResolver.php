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

namespace JMS\Serializer;

use JMS\Serializer\Annotation\TypeResolver;
use JMS\Serializer\Exception\RuntimeException;

class SForceTypeResolver implements TypeResolver
{
    const WSDL_NS = 'SForce\\Wsdl\\';

    const RETURN_PLAIN = '%s';
    const RETURN_ARRAY = 'array<%s>';

    const TYPE_NULL = 'null';
    const TYPE_BOOLEAN = 'bool';
    const TYPE_FLOAT = 'float';
    const TYPE_STRING = 'string';
    const TYPE_INTEGER = 'int';

    /**
     * @var array
     */
    private static $typeAliasMap = [
        'ID' => self::TYPE_STRING,
        'boolean' => self::TYPE_BOOLEAN,
        'double' => self::TYPE_FLOAT,
        'void' => self::TYPE_NULL,
        'base64Binary' => self::TYPE_STRING,
        'soapType' => self::TYPE_STRING,
        'fieldType' => self::TYPE_STRING,
    ];

    /**
     * @var string[]
     */
    private static $simpleTypes = [
        self::TYPE_NULL,
        self::TYPE_BOOLEAN,
        self::TYPE_INTEGER,
        self::TYPE_FLOAT,
        self::TYPE_STRING,
    ];

    /**
     * @var array
     */
    private $typeCache = [];

    /**
     * @param string $type
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    public function resolve($type)
    {
        if (isset($this->typeCache[$type])) {
            return $this->typeCache[$type];
        }

        $returnTpl = self::RETURN_PLAIN;
        $origType = $type;

        // If it's an array remove brackets and change return template
        if (substr($type, -2) === '[]') {
            $type = substr($type, 0, -2);
            $returnTpl = self::RETURN_ARRAY;
        }

        // Fully-qualified classes
        if ($type[0] === '\\') {
            return $this->typeCache[$origType] = sprintf($returnTpl, ltrim($type, '\\'));
        }

        // Aliases of known simple types
        if (isset(self::$typeAliasMap[$type])) {
            return $this->typeCache[$origType] = sprintf($returnTpl, self::$typeAliasMap[$type]);
        }

        // Known simple types
        if (in_array($type, self::$simpleTypes, true)) {
            return $this->typeCache[$origType] = sprintf($returnTpl, $type);
        }

        // Classes in SForce namespace
        if (class_exists(self::WSDL_NS . $type)) {
            return $this->typeCache[$origType] = sprintf($returnTpl, self::WSDL_NS . $type);
        }

        throw new RuntimeException("Type '$origType' is not supported or known.");
    }
}
