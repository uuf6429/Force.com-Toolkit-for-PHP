<?php

namespace SForce\Exception;

class UnsupportedFieldTypeException extends \RuntimeException
{
    public function __construct($type)
    {
        parent::__construct("Unsupported type: $type");
    }
}
