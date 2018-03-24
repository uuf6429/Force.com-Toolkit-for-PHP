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

/**
 * Read-only value object base class. Properties are added by adding '@property' annotations to your child class.
 */
abstract class ValueObject
{
    /**
     * @var array
     */
    private $data = [];

    /**
     * @param null|array $data
     */
    public function __construct(array $data = null)
    {
        if ($data === null) {
            $this->data = array_fill_keys($this->getProperties(), null);
        } else {
            $this->fromArray($data);
        }
    }

    /**
     * @param string $name
     *
     * @return mixed
     *
     * @throws \OutOfBoundsException
     */
    public function __get($name)
    {
        if (!array_key_exists($name, $this->data)) {
            throw new \OutOfBoundsException(sprintf('Property "%s" not found for %s.', $name, static::class));
        }

        return $this->data[$name];
    }

    /**
     * @param string $name
     * @param mixed $value
     *
     * @throws \RuntimeException
     */
    public function __set($name, $value)
    {
        throw new \RuntimeException(sprintf('Property "%s" is not writable for %s.', $name, static::class));
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return isset($this->data[$name]);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function fromArray(array $data)
    {
        $expectedProperties = $this->getProperties();

        if (!is_array($data)) {
            throw new \InvalidArgumentException('Data parameter should be an array.');
        }

        $missingFields = implode(', ', array_diff($expectedProperties, array_keys($data)));
        if ($missingFields) {
            throw new \InvalidArgumentException("Data parameter is missing one or more fields: $missingFields.");
        }

        foreach ($expectedProperties as $key) {
            $this->data[$key] = $data[$key];
        }
    }

    /**
     * @param null|string $class
     *
     * @return string[]
     */
    private function getProperties($class = null)
    {
        static $propCache = [
            'stdClass' => [],
            __CLASS__ => [],
        ];

        if (!$class) {
            $class = static::class;
        }

        if (!isset($propCache[$class])) {
            $properties = $this->getProperties(get_parent_class($class));

            if (preg_match_all(
                '/\\*\\s+@property\\s+[\\\\\\w\\|]*\\s*\\$([a-zA-Z_\\x7f-\\xff][a-zA-Z0-9_\\x7f-\\xff]*)/',
                (new \ReflectionClass($class))->getDocComment(),
                $matches
            )) {
                $properties = array_merge($properties, $matches[1]);
            }

            $propCache[$class] = $properties;
        }

        return $propCache[$class];
    }
}
