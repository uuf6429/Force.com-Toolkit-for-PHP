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

class QueryResult implements \Iterator
{
    public $queryLocator;
    public $done;
    public $records;
    public $size;

    public $pointer; // Current iterator location
    private $sf; // SOAP Client

    public function __construct($response)
    {
        $this->queryLocator = $response->queryLocator;
        $this->done = $response->done;
        $this->size = $response->size;

        $this->pointer = 0;
        $this->sf = false;

        if ($response instanceof self) {
            $this->records = $response->records;
        } else {
            $this->records = [];
            if (isset($response->records)) {
                if (is_array($response->records)) {
                    foreach ($response->records as $record) {
                        $this->records[] = $record;
                    }
                } else {
                    $this->records[] = $response->records;
                }
            }
        }
    }

    public function setSf(Client\Base $sf)
    {
        $this->sf = $sf;
    } // Dependency Injection

    // Basic Iterator implementation functions
    public function rewind()
    {
        $this->pointer = 0;
    }

    public function next()
    {
        ++ $this->pointer;
    }

    public function key()
    {
        return $this->pointer;
    }

    public function current()
    {
        return new SObject($this->records[$this->pointer]);
    }

    public function valid()
    {
        while ($this->pointer >= count($this->records)) {
            // Pointer is larger than (current) result set; see if we can fetch more
            if ($this->done === false) {
                if ($this->sf === false) {
                    throw new \Exception('Dependency not met!');
                }
                $response = $this->sf->queryMore($this->queryLocator);
                $this->records = array_merge($this->records, $response->records); // Append more results
                $this->done = $response->done;
                $this->queryLocator = $response->queryLocator;
            } else {
                return false; // No more records to fetch
            }
        }
        if (isset($this->records[$this->pointer])) {
            return true;
        }

        throw new \Exception('QueryResult has gaps in the record data?');
    }
}
