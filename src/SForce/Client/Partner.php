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

namespace SForce\Client;

use SForce\Soap\SoapClient;
use SForce\SObject;

class Partner extends Base
{
    const PARTNER_NAMESPACE = 'urn:partner.soap.sforce.com';

    public function __construct()
    {
        $this->namespace = self::PARTNER_NAMESPACE;
    }

    /**
     * @inheritdoc
     */
    public function createConnection($wsdl = null, $proxy = null, array $soapOptions = [])
    {
        if ($wsdl === null) {
            $wsdl = __DIR__ . '/../Wsdl/partner.wsdl.xml';
        }

        return parent::createConnection($wsdl, $proxy, $soapOptions);
    }

    /**
     * @inheritdoc
     */
    protected function createSoapClient($wsdl, $options)
    {
        // Workaround an issue in parsing OldValue and NewValue in histories
        return new SoapClient($options, $wsdl);
    }

    /**
     * Adds one or more new individual objects to your organization's data.
     *
     * @param SObject[] $sObjects Array of one or more sObjects (up to 200) to create.
     * @param null|string $type Unused
     *
     * @return SaveResult
     */
    public function create($sObjects, $type = null)
    {
        $arg = new \stdClass;
        foreach ($sObjects as $sObject) {
            if (property_exists('fields', $sObject)) {
                $sObject->setAny($this->_convertToAny($sObject->getFields()));
            }
        }
        $arg->sObjects = $sObjects;

        return parent::_create($arg);
    }

    /**
     * Merge records
     *
     * @param \stdclass $mergeRequest
     *
     * @return mixed
     */
    public function merge($mergeRequest)
    {
        if (isset($mergeRequest->masterRecord)) {
            if (isset($mergeRequest->masterRecord->fields)) {
                $mergeRequest->masterRecord->any = $this->_convertToAny($mergeRequest->masterRecord->fields);
            }
            $arg = new \stdClass();
            $arg->request = $mergeRequest;

            return $this->_merge($arg);
        }
    }

    /**
     * Updates one or more new individual objects to your organization's data.
     *
     * @param array $sObjects    Array of sObjects
     *
     * @return UpdateResult
     */
    public function update($sObjects)
    {
        $arg = new \stdClass;
        foreach ($sObjects as $sObject) {
            if (isset($sObject->fields)) {
                $sObject->any = $this->_convertToAny($sObject->fields);
            }
        }
        $arg->sObjects = $sObjects;

        return parent::_update($arg);
    }

    /**
     * Creates new objects and updates existing objects; uses a custom field to
     * determine the presence of existing objects. In most cases, we recommend
     * that you use upsert instead of create because upsert is idempotent.
     * Available in the API version 7.0 and later.
     *
     * @param string $ext_Id External Id
     * @param array $sObjects Array of sObjects
     *
     * @return UpsertResult
     */
    public function upsert($ext_Id, $sObjects)
    {
        $arg = new \stdClass;
        $arg->externalIDFieldName = new \SoapVar($ext_Id, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        foreach ($sObjects as $sObject) {
            if (isset($sObject->fields)) {
                $sObject->any = $this->_convertToAny($sObject->fields);
            }
        }
        $arg->sObjects = $sObjects;

        return parent::_upsert($arg);
    }
}
