<?php

namespace JMS\Serializer;

use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\Metadata\PropertyMetadata;

class ObjectDeserializationVisitor extends GenericDeserializationVisitor
{
    /**
     * @inheritdoc
     */
    protected function decode($str)
    {
        if (!is_object($str)) {
            throw new RuntimeException('An object graph was expected.');
        }

        return $str;
    }

    /**
     * @inheritdoc
     */
    public function visitProperty(PropertyMetadata $metadata, $data, Context $context)
    {
        parent::visitProperty($metadata, is_object($data) ? (array)$data : $data, $context);
    }
}
