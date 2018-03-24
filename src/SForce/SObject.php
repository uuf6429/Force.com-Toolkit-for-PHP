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

class SObject
{
    public $Id;
    public $type;
    public $fields;

    /**
     * @param null|\stdClass $response
     * @param Client\Base $sfClient
     */
    public function __construct($response, Client\Base $sfClient)
    {
        if (!$response) {
            return;
        }

        foreach ($response as $responseKey => $responseValue) {
            if (in_array((string)$responseKey, ['Id', 'type', 'any'], true)) {
                continue;
            }
            $this->$responseKey = $responseValue;
        }

        if (isset($response->Id)) {
            $this->Id = is_array($response->Id) ? $response->Id[0] : $response->Id;
        }

        if (isset($response->type)) {
            $this->type = $response->type;
        }

        if (isset($response->any)) {
            //$this->fields = $this->convertFields($response->any);
            // If ANY is an object, instantiate another SObject
            if ($response->any instanceof \stdClass) {
                if ($this->isSObject($response->any)) {
                    $anArray = [];
                    $sobject = new SObject($response->any, $sfClient);
                    $anArray[] = $sobject;
                    $this->sobjects = $anArray;
                } else {
                    // this is for parent to child relationships
                    $this->queryResult = new QueryResult($response->any, $sfClient);
                }
            } else {
                // If ANY is an array
                if (is_array($response->any)) {
                    // Loop through each and perform some action.
                    $anArray = [];

                    // Modify the foreach to have $key=>$value
                    // Added on 28th April 2008
                    foreach ($response->any as $key => $item) {
                        if ($item instanceof \stdClass) {
                            if ($this->isSObject($item)) {
                                // make an associative array instead of a numeric one
                                $anArray[$key] = new SObject($item, $sfClient);
                            } else {
                                // this is for parent to child relationships
                                //$this->queryResult = new QueryResult($item);
                                if (!isset($this->queryResult)) {
                                    $this->queryResult = [];
                                }
                                $this->queryResult[] = new QueryResult($item, $sfClient);
                            }
                        } else {
                            //$this->fields = $this->convertFields($item);

                            if (strpos($item, 'sf:') === false) {
                                $currentXmlValue = sprintf('<sf:%s>%s</sf:%s>', $key, $item, $key);
                            } else {
                                $currentXmlValue = $item;
                            }

                            if (!isset($fieldsToConvert)) {
                                $fieldsToConvert = $currentXmlValue;
                            } else {
                                $fieldsToConvert .= $currentXmlValue;
                            }
                        }
                    }

                    if (isset($fieldsToConvert)) {
                        $this->fields = $this->convertFields($fieldsToConvert);
                    }

                    if (count($anArray)) {
                        foreach ($anArray as $key => $children_sobject) {
                            $this->fields->$key = $children_sobject;
                        }
                    }
                } else {
                    $this->fields = $this->convertFields($response->any);
                }
            }
        }
    }

    public function __get($name)
    {
        return isset($this->fields->$name) ? $this->fields->$name : false;
    }

    public function __set($name, $value)
    {
        $this->fields->$name = $value;
    }

    public function __isset($name)
    {
        return isset($this->fields->$name);
    }

    /**
     * Parse the "any" string from an sObject.  First strip out the sf: and then
     * enclose string with <Object></Object>.  Load the string using
     * simplexml_load_string and return an array that can be traversed.
     */
    public function convertFields($any)
    {
        $str = preg_replace('{sf:}', '', $any);

        $array = $this->xml2array('<Object xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">' . $str . '</Object>', 2);

        $xml = new \stdClass();
        if (!count($array['Object'])) {
            return $xml;
        }

        foreach ($array['Object'] as $k => $v) {
            $xml->$k = $v;
        }

        //$new_string = '<Object xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'.$new_string.'</Object>';
        //$new_string = $new_string;
        //$xml = simplexml_load_string($new_string);
        return $xml;
    }

    /**
     *
     * @param string $contents
     *
     * @return array
     */
    public function xml2array($contents, $get_attributes = 1)
    {
        if (!$contents) {
            return [];
        }

        if (!function_exists('xml_parser_create')) {
            //print "'xml_parser_create()' function not found!";
            return ['not found'];
        }
        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $contents, $xml_values);
        xml_parser_free($parser);

        if (!$xml_values) {
            return [];
        }//Hmm...

        //Initializations
        $xml_array = [];
        $parents = [];
        $opened_tags = [];
        $arr = [];

        $current = &$xml_array;

        //Go through the tags.
        foreach ($xml_values as $data) {
            unset($attributes, $value);//Remove existing values, or there will be trouble

            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data);//We could use the array by itself, but this cooler.

            $result = '';
            if ($get_attributes) {
                switch ($get_attributes) {
                    case 1:
                        $result = [];
                        if (isset($value)) {
                            $result['value'] = $value;
                        }

                        //Set the attributes too.
                        if (isset($attributes)) {
                            foreach ($attributes as $attr => $val) {
                                if ($get_attributes == 1) {
                                    $result['attr'][$attr] = $val;
                                } //Set all the attributes in a array called 'attr'
                                /**  :TODO: should we change the key name to '_attr'? Someone may use the tagname 'attr'. Same goes for 'value' too */
                            }
                        }
                        break;

                    case 2:
                        $result = [];
                        if (isset($value)) {
                            $result = $value;
                        }

                        //Check for nil and ignore other attributes.
                        if (isset($attributes) && isset($attributes['xsi:nil']) && !strcasecmp($attributes['xsi:nil'], 'true')) {
                            $result = null;
                        }
                        break;
                }
            } elseif (isset($value)) {
                $result = $value;
            }

            //See tag status and do the needed.
            if ($type === 'open') {//The starting of the tag '<tag>'
                $parent[$level - 1] = &$current;

                if (!is_array($current) or (!array_key_exists($tag, $current))) { //Insert New tag
                    $current[$tag] = $result;
                    $current = &$current[$tag];
                } else { //There was another element with the same tag name
                    if (isset($current[$tag][0])) {
                        $current[$tag][] = $result;
                    } else {
                        $current[$tag] = [$current[$tag], $result];
                    }
                    $last = count($current[$tag]) - 1;
                    $current = &$current[$tag][$last];
                }
            } elseif ($type === 'complete') { //Tags that ends in 1 line '<tag />'
                //See if the key is already taken.
                if (!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                } else { //If taken, put all things inside a list(array)
                    if ((is_array($current[$tag]) && $get_attributes == 0)//If it is already an array...
                        || (isset($current[$tag][0]) && is_array($current[$tag][0]) && ($get_attributes == 1 || $get_attributes == 2))) {
                        $current[$tag][] = $result; // ...push the new element into that array.
                    } else { //If it is not an array...
                        $current[$tag] = [
                            $current[$tag],
                            $result,
                        ]; //...Make it an array using using the existing value and the new value
                    }
                }
            } elseif ($type === 'close') { //End of tag '</tag>'
                $current = &$parent[$level - 1];
            }
        }

        return $xml_array;
    }

    /*
     * If the stdClass has a done, we know it is a QueryResult
     */
    public function isQueryResult($param)
    {
        return isset($param->done);
    }

    /*
     * If the stdClass has a type, we know it is an SObject
     */
    public function isSObject($param)
    {
        return isset($param->type);
    }
}
