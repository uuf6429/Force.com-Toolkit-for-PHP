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
 * @todo Figure out usage of this class and clean up the various issues.
 */
class Metadata
{
    /**
     * @var \SoapClient
     */
    protected $sforce;

    /**
     * @var
     */
    protected $sessionId;

    /**
     * @var
     */
    protected $location;

    /**
     * @var string
     */
    protected $version = '27.0';

    /**
     * @var string
     */
    protected $namespace = 'http://soap.sforce.com/2006/04/metadata';

    /**
     * @param string $wsdl
     * @param $loginResult
     * @param $sforceConn
     */
    public function __construct($wsdl, $loginResult, $sforceConn)
    {
        $soapClientArray = [
            'user_agent' => 'salesforce-toolkit-php/' . $this->version,
            'encoding' => 'utf-8',
            'trace' => 1,
            'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            'sessionId' => $loginResult->sessionId,
        ];
        $this->sforce = new \SoapClient($wsdl, $soapClientArray);

        $sessionVar = [
            'sessionId' => new \SoapVar($loginResult->sessionId, XSD_STRING),
        ];

        $headerBody = new \SoapVar($sessionVar, SOAP_ENC_OBJECT);

        $session_header = new \SoapHeader($this->namespace, 'SessionHeader', $headerBody, false);

        $header_array = [
            $session_header,
        ];

        $this->sforce->__setSoapHeaders($header_array);

        $this->sforce->__setLocation($loginResult->metadataServerUrl);
    }

    /**
     * Specifies the session ID returned from the login server after a successful login.
     */
    protected function _setLoginHeader($loginResult)
    {
        $this->sessionId = $loginResult->sessionId;
        $this->setSessionHeader($this->sessionId);
        $serverURL = $loginResult->serverUrl;
        $this->setEndpoint($serverURL);
    }

    /**
     * Set the endpoint.
     *
     * @param string $location Location
     */
    public function setEndpoint($location)
    {
        $this->location = $location;
        $this->sforce->__setLocation($location);
    }

    /**
     * Set the Session ID
     *
     * @param string $sessionId Session ID
     */
    public function setSessionHeader($sessionId)
    {
        $this->sforce->__setSoapHeaders(null);
        $session_header = new \SoapHeader($this->namespace, 'SessionHeader', [
            'sessionId' => $sessionId,
        ]);
        $this->sessionId = $sessionId;
        $header_array = [
            $session_header,
        ];
        $this->_setClientId($header_array);
        $this->sforce->__setSoapHeaders($header_array);
    }

    private function getObjType($obj)
    {
        $classArray = explode('\\', get_class($obj));
        $objtype = array_pop($classArray);
        if (strpos($objtype, 'Sforce', 0) === 0) {
            $objtype = substr($objtype, 6);
        }

        return $objtype;
    }

    public function create($obj)
    {
        $encodedObj = new \stdClass();
        $encodedObj->metadata = new \SoapVar($obj, SOAP_ENC_OBJECT, $this->getObjType($obj), $this->namespace);

        return $this->sforce->create($encodedObj);
    }

    public function update($obj)
    {
        $encodedObj = new \stdClass();
        $encodedObj->UpdateMetadata = $obj;
        $encodedObj->UpdateMetadata->metadata = new \SoapVar($obj->metadata, SOAP_ENC_OBJECT, $this->getObjType($obj->metadata), $this->namespace);

        return $this->sforce->update($encodedObj);
    }

    public function delete($obj)
    {
        $encodedObj = new \stdClass();
        $encodedObj->metadata = new \SoapVar($obj, SOAP_ENC_OBJECT, $this->getObjType($obj), $this->namespace);

        return $this->sforce->delete($encodedObj);
    }

    public function checkStatus($ids)
    {
        return $this->sforce->checkStatus($ids);
    }

    public function getLastRequest()
    {
        return $this->sforce->__getLastRequest();
    }

    public function getLastRequestHeaders()
    {
        return $this->sforce->__getLastRequestHeaders();
    }

    public function getLastResponse()
    {
        return $this->sforce->__getLastResponse();
    }

    public function getLastResponseHeaders()
    {
        return $this->sforce->__getLastResponseHeaders();
    }
}