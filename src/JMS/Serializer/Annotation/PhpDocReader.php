<?php

namespace JMS\Serializer\Annotation;

use Doctrine\Common\Annotations\Reader;

class PhpDocReader implements Reader
{
    /**
     * @var callable
     */
    private $typeResolver;

    /**
     * @param callable $typeResolver
     */
    public function __construct(callable $typeResolver)
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
        return null;
    }

    /**
     * @inheritdoc
     */
    public function getPropertyAnnotations(\ReflectionProperty $property)
    {
        $typeAnnotation = $this->getPropertyAnnotation($property, 'type');

        return $typeAnnotation ? [$typeAnnotation] : [];
    }

    /**
     * @inheritdoc
     */
    public function getPropertyAnnotation(\ReflectionProperty $property, $annotationName)
    {
        $type = $this->parsePropertyType($property->getDocComment());

        if ($type === '') {
            return null;
        }

        $attr = new Type();
        $attr->name = $type;

        return $attr;
    }

    /**
     * @param string $docBlock
     *
     * @return string
     *
     * @todo What about resolving to FQN?
     */
    protected function parsePropertyType($docBlock)
    {
        if (preg_match('/\\*\\s+@var\\s+([\\w_\\|<>\\[\\]\\\\]+)/', $docBlock, $match)) {
            $match = explode('|', $match[1]);

            return implode(
                '|',
                array_unique(
                    array_map($this->typeResolver, $match)
                )
            );
        }

        return '';
    }
}
