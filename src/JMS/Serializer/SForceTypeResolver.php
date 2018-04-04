<?php

namespace JMS\Serializer;

use JMS\Serializer\Annotation\TypeResolver;

class SForceTypeResolver implements TypeResolver
{
    const WSDL_NS = 'SForce\\Wsdl\\';

    const RETURN_PLAIN = '%s';
    const RETURN_ARRAY = 'array<%s>';

    /**
     * @var array
     */
    private static $typeAliasMap = [
        'ID' => 'string',
        'boolean' => 'bool',
        'double' => 'float',
        'void' => 'null',
        'base64Binary' => 'string',
        'soapType' => 'string',
        'fieldType' => 'string',
    ];

    /**
     * @var string[]
     */
    private static $simpleTypes = ['null', 'bool', 'int', 'float', 'string'];

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

        // Aliases of known simple types
        if (isset(self::$typeAliasMap[$type])) {
            return $this->typeCache[$origType] = sprintf($returnTpl, self::$typeAliasMap[$type]);
        }

        // Known simple types
        if (in_array($type, self::$simpleTypes, true)) {
            return $this->typeCache[$origType] = sprintf($returnTpl, $type);
        }

        // Classes in SForce namespace
        if ($type[0] !== '\\' && class_exists(self::WSDL_NS . $type)) {
            return $this->typeCache[$origType] = sprintf($returnTpl, self::WSDL_NS . $type);
        }

        if (class_exists($type)) {
            return $this->typeCache[$origType] = sprintf($returnTpl, ltrim($type, '\\'));
        }

        throw new \RuntimeException("Type '$type' is not supported or known.");
    }
}