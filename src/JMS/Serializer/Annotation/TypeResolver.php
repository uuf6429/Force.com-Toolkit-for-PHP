<?php

namespace JMS\Serializer\Annotation;

interface TypeResolver
{
    /**
     * @param string $type
     *
     * @return string
     */
    public function resolve($type);
}
