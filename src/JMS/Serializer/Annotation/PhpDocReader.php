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

namespace JMS\Serializer\Annotation;

use Doctrine\Common\Annotations\Reader;

class PhpDocReader implements Reader
{
    /**
     * @var TypeResolver
     */
    private $typeResolver;

    /**
     * @param TypeResolver $typeResolver
     */
    public function __construct(TypeResolver $typeResolver)
    {
        $this->typeResolver = $typeResolver;
    }

    /**
     * @inheritdoc
     */
    public function getClassAnnotations(\ReflectionClass $class)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getClassAnnotation(\ReflectionClass $class, $annotationName)
    {
        $annotations = $this->getClassAnnotations($class);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getMethodAnnotations(\ReflectionMethod $method)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public function getMethodAnnotation(\ReflectionMethod $method, $annotationName)
    {
        $annotations = $this->getMethodAnnotations($method);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        $result = [];
        $type = $this->parsePropertyType($property->getDocComment());
        $isDateType = in_array($type, [\DateTime::class, \DateTimeImmutable::class], true);

        // add property type
        if ($type !== '') {
            $attr = new Type();
            $attr->name = $isDateType ? 'string' : $type;
            $result[] = $attr;
        }

        // add property getter/setter
        $method = ucfirst($property->getName());
        $declaringClass = $property->getDeclaringClass();
        $accessor = new Accessor();
        if ($declaringClass->hasMethod("get$method")) {
            $accessor->getter = "get$method";
        }
        // setter for date(time) types is not compatible with string, so we ignore setter
        if (!$isDateType && $declaringClass->hasMethod("set$method")) {
            $accessor->setter = "set$method";
        }
        $result[] = $accessor;

        return $result;
    }

    /**
     * @inheritdoc
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        $annotations = $this->getPropertyAnnotations($property);

        foreach ($annotations as $annotation) {
            if ($annotation instanceof $annotationName) {
                return $annotation;
            }
        }

        return null;
    }

    /**
     * @param string $docBlock
     *
     * @return string
     */
    protected function parsePropertyType($docBlock)
    {
        if (preg_match('/\\*\\s+@var\\s+([\\w_\\|<>\\[\\]\\\\]+)/', $docBlock, $match)) {
            $match = explode('|', $match[1]);

            return implode(
                '|',
                array_unique(
                    array_map([$this->typeResolver, 'resolve'], $match)
                )
            );
        }

        return '';
    }
}
