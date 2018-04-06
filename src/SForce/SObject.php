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

class SObject extends Wsdl\sObject
{
    /**
     * @var null|array
     */
    protected $fields;

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $this->initFields();

        return isset($this->fields[$name]) ? $this->fields[$name] : false;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->initFields();

        $this->fields[$name] = $value;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        $this->initFields();

        return isset($this->fields[$name]);
    }

    /**
     * @return string
     */
    public function getId()
    {
        return is_array($this->Id) ? $this->Id[0] : $this->Id;
    }

    /**
     * @return object
     */
    public function getFields()
    {
        $this->initFields();

        return (object)$this->fields;
    }

    /**
     * Parse the "any" string from an sObject.  First strip out the sf: and then
     * enclose string with <Object></Object>.  Load the string using
     * simplexml_load_string and return an array that can be traversed.
     *
     * @param string $any
     *
     * @return array
     */
    protected function convertFields($any)
    {
        static $root = 'Object';
        $str = preg_replace('{sf:}', '', $any);

        $array = $this->xml2array(
            "<$root xmlns:xsd='http://www.w3.org/2001/XMLSchema' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'>$str</$root>",
            2
        );

        return (array)$array[$root];
    }

    /**
     *
     * @param string $contents
     * @param int $getAttributes
     *
     * @return array
     */
    public function xml2array($contents, $getAttributes = 1)
    {
        if (!$contents) {
            return [];
        }

        if (!function_exists('xml_parser_create')) {
            // TODO "'xml_parser_create()' function not found!";
            return ['not found'];
        }
        //Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create();
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, $contents, $xmlValues);
        xml_parser_free($parser);

        if (!$xmlValues) {
            return [];
        }//Hmm...

        //Initializations
        $xml_array = [];

        $current = &$xml_array;

        //Go through the tags.
        foreach ($xmlValues as $data) {
            unset($attributes, $value);//Remove existing values, or there will be trouble

            //This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data);//We could use the array by itself, but this cooler.

            $result = '';
            if ($getAttributes) {
                switch ($getAttributes) {
                    case 1:
                        $result = [];
                        if (isset($value)) {
                            $result['value'] = $value;
                        }

                        //Set the attributes too.
                        if (isset($attributes)) {
                            foreach ($attributes as $attr => $val) {
                                if ($getAttributes == 1) {
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
                        if (isset($attributes, $attributes['xsi:nil']) && !strcasecmp($attributes['xsi:nil'], 'true')) {
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

                if (!is_array($current) || (!array_key_exists($tag, $current))) { //Insert New tag
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
                    if ((is_array($current[$tag]) && $getAttributes == 0)//If it is already an array...
                        || (isset($current[$tag][0]) && is_array($current[$tag][0]) && ($getAttributes == 1 || $getAttributes == 2))) {
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

    /**
     * If the stdClass has a done, we know it is a QueryResult
     *
     * @deprecated Do not use, this will be removed.
     */
    public function isQueryResult($param)
    {
        return isset($param->done);
    }

    /**
     * If the stdClass has a type, we know it is an SObject
     *
     * @deprecated Do not use, this will be removed.
     */
    public function isSObject($param)
    {
        return isset($param->type);
    }

    protected function initFields()
    {
        if ($this->fields === null && $this->any !== null) {
            $this->fields = $this->convertFields($this->any);
        }
    }
}
