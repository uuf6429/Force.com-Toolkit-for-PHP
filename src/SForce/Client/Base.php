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

use SForce\Wsdl;
use SForce\Exception\NotConnectedException;
use SForce\QueryResult;
use SForce\SearchResult;
use SForce\SObject;

abstract class Base
{
    const CALL_CONVERT_LEAD = 'convertLead';
    const CALL_CREATE = 'create';
    const CALL_DELETE = 'delete';
    const CALL_DESCRIBE_DATA_CATEGORY_GROUP_STRUCTURES = 'describeDataCategoryGroupStructures';
    const CALL_DESCRIBE_DATA_CATEGORY_GROUPS = 'describeDataCategoryGroups';
    const CALL_DESCRIBE_GLOBAL = 'describeGlobal';
    const CALL_DESCRIBE_LAYOUT = 'describeLayout';
    const CALL_DESCRIBE_SOBJECT = 'describeSObject';
    const CALL_DESCRIBE_SOBJECTS = 'describeSObjects';
    const CALL_DESCRIBE_SOFTPHONE_LAYOUT = 'describeSoftphoneLayout';
    const CALL_DESCRIBE_TABS = 'describeTabs';
    const CALL_GET_DELETED = 'getDeleted';
    const CALL_GET_SERVER_TIMESTAMP = 'getServerTimestamp';
    const CALL_GET_UPDATED = 'getUpdated';
    const CALL_GET_USER_INFO = 'getUserInfo';
    const CALL_LOGIN = 'login';
    const CALL_MERGE = 'merge';
    const CALL_PROCESS = 'process';
    const CALL_QUERY = 'query';
    const CALL_QUERY_ALL = 'queryAll';
    const CALL_QUERY_MORE = 'queryMore';
    const CALL_RESET_PASSWORD = 'resetPassword';
    const CALL_RETRIEVE = 'retrieve';
    const CALL_SEARCH = 'search';
    const CALL_SET_PASSWORD = 'setPassword';
    const CALL_UNDELETE = 'undelete';
    const CALL_UPDATE = 'update';
    const CALL_UPSERT = 'upsert';

    /**
     * @var \SoapClient
     */
    protected $sforce;
    protected $sessionId;
    protected $location;
    protected $version = '27.0';

    protected $namespace;

    // Header Options
    protected $callOptions;
    protected $assignmentRuleHeader;
    protected $emailHeader;
    protected $loginScopeHeader;
    protected $mruHeader;
    protected $queryHeader;
    protected $userTerritoryDeleteHeader;
    protected $sessionHeader;

    // new headers
    protected $allowFieldTruncationHeader;
    protected $localeOptions;
    protected $packageVersionHeader;

    public function getNamespace()
    {
        return $this->namespace;
    }


    // clientId specifies which application or toolkit is accessing the
    // salesforce.com API. For applications that are certified salesforce.com
    // solutions, replace this with the value provided by salesforce.com.
    // Otherwise, leave this value as 'phpClient/1.0'.
    protected $client_id;

    /**
     * @param string $wsdl
     * @param array $options
     *
     * @return \SoapClient
     */
    protected function getSoapClient($wsdl, $options)
    {
        return new \SoapClient($wsdl, $options);
    }

    /**
     * Connect method to www.salesforce.com
     *
     * @param string $wsdl Salesforce.com Partner WSDL
     * @param null|\SForce\ProxySettings $proxy (optional) proxy settings with properties host, port,
     *                       login and password
     * @param array $soapOptions (optional) Additional options to send to the
     *                       SoapClient constructor. @see
     *                       http://php.net/manual/en/soapclient.soapclient.php
     * @return \SoapClient
     */
    public function createConnection($wsdl, $proxy = null, array $soapOptions = [])
    {
        $soapClientArray = array_merge(
            [
                'user_agent' => "salesforce-toolkit-php/{$this->version}",
                'encoding' => 'utf-8',
                'trace' => 1,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
            ],
            $soapOptions
        );

        if ($proxy !== null) {
            $soapClientArray = array_merge($soapClientArray, $proxy->toArray());
        }

        $this->sforce = $this->getSoapClient($wsdl, $soapClientArray);

        return $this->sforce;
    }

    /**
     * Login to Salesforce.com and starts a client session.
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return Wsdl\LoginResult
     *
     * @throws NotConnectedException
     */
    public function login($username, $password)
    {
        if (!$this->sforce) {
            throw new NotConnectedException('Connection has not been created yet.');
        }

        $this->sforce->__setSoapHeaders(null);
        if ($this->callOptions !== null) {
            $this->sforce->__setSoapHeaders([$this->callOptions]);
        }
        if ($this->loginScopeHeader !== null) {
            $this->sforce->__setSoapHeaders([$this->loginScopeHeader]);
        }
        $response = $this->sforce->login([
            'username' => $username,
            'password' => $password,
        ]);
        
        $result = new Wsdl\LoginResult(null, null);
        $this->fromSoapResponse($result, $response->result);
        
        $this->_setLoginHeader($result);

        return $result;
    }

    /**
     * Logs out from the Salesforce.com system
     *
     * @return Wsdl\logoutResponse
     */
    public function logout()
    {
        $this->setHeaders('logout');

        $response = $this->sforce->logout();

        $result = new Wsdl\logoutResponse();
        $this->fromSoapResponse($result, $response->result);

        return $result;
    }

    /**
     * Invalidates sessions from the Salseforce.com system
     *
     * @return Wsdl\invalidateSessionsResult
     */
    public function invalidateSessions()
    {
        $this->setHeaders('invalidateSessions');
        $this->logout();

        $response = $this->sforce->invalidateSessions();

        $result = new Wsdl\InvalidateSessionsResult(null);
        $this->fromSoapResponse($result, $response->result);

        return $result;
    }

    /**
     * Specifies the session ID returned from the login server after a successful login.
     *
     * @param Wsdl\LoginResult $loginResult
     */
    protected function _setLoginHeader(Wsdl\LoginResult $loginResult)
    {
        $this->sessionId = $loginResult->getSessionId();
        $this->setSessionHeader(new Wsdl\SessionHeader($this->sessionId));
        $serverURL = $loginResult->getServerUrl();
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
     * @param null|string $call One of self::CALL_* constants.
     */
    private function setHeaders($call = null)
    {
        $this->sforce->__setSoapHeaders(null);

        $header_array = [
            $this->sessionHeader,
        ];

        $header = $this->callOptions;
        if ($header !== null) {
            $header_array[] = $header;
        }

        if (in_array($call, [
            static::CALL_CREATE,
            static::CALL_MERGE,
            static::CALL_UPDATE,
            static::CALL_UPSERT,
        ], true)) {
            $header = $this->assignmentRuleHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        if ($call === static::CALL_LOGIN) {
            $header = $this->loginScopeHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        if (in_array($call, [
            static::CALL_CREATE,
            static::CALL_RESET_PASSWORD,
            static::CALL_UPDATE,
            static::CALL_UPSERT,
        ], true)) {
            $header = $this->emailHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        if (in_array($call, [
            static::CALL_CREATE,
            static::CALL_MERGE,
            static::CALL_QUERY,
            static::CALL_RETRIEVE,
            static::CALL_UPDATE,
            static::CALL_UPSERT,
        ], true)) {
            $header = $this->mruHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        if ($call === static::CALL_DELETE) {
            $header = $this->userTerritoryDeleteHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        if (in_array($call, [
            static::CALL_QUERY,
            static::CALL_QUERY_MORE,
            static::CALL_RETRIEVE,
        ], true)) {
            $header = $this->queryHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        // try to add allowFieldTruncationHeader
        if (in_array($call, [
            static::CALL_CONVERT_LEAD,
            static::CALL_CREATE,
            static::CALL_MERGE,
            static::CALL_PROCESS,
            static::CALL_UNDELETE,
            static::CALL_UPDATE,
            static::CALL_UPSERT,
        ], true)) {
            $header = $this->allowFieldTruncationHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        // try to add localeOptions
        if ($call === static::CALL_DESCRIBE_SOBJECT
            || $call === static::CALL_DESCRIBE_SOBJECTS
        ) {
            $header = $this->localeOptions;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        // try to add PackageVersionHeader
        $packageVersionHeaderCalls = [
            static::CALL_CONVERT_LEAD,
            static::CALL_CREATE,
            static::CALL_DELETE,
            static::CALL_DESCRIBE_GLOBAL,
            static::CALL_DESCRIBE_LAYOUT,
            static::CALL_DESCRIBE_SOBJECT,
            static::CALL_DESCRIBE_SOBJECTS,
            static::CALL_DESCRIBE_SOFTPHONE_LAYOUT,
            static::CALL_DESCRIBE_TABS,
            static::CALL_MERGE,
            static::CALL_PROCESS,
            static::CALL_QUERY,
            static::CALL_RETRIEVE,
            static::CALL_SEARCH,
            static::CALL_UNDELETE,
            static::CALL_UPDATE,
            static::CALL_UPSERT,
        ];
        if (in_array($call, $packageVersionHeaderCalls, true)) {
            $header = $this->packageVersionHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        $this->sforce->__setSoapHeaders($header_array);
    }

    /**
     * @param Wsdl\CallOptions $header
     */
    public function setCallOptions(Wsdl\CallOptions $header)
    {
        $this->callOptions = $this->toSoapHeader($header, $this->namespace);
    }

    /**
     * @param Wsdl\AssignmentRuleHeader $header
     */
    public function setAssignmentRuleHeader(Wsdl\AssignmentRuleHeader $header)
    {
        $this->assignmentRuleHeader = $this->toSoapHeader($header, $this->namespace);
    }

    /**
     * @param Wsdl\EmailHeader $header
     */
    public function setEmailHeader(Wsdl\EmailHeader $header)
    {
        $this->emailHeader = $this->toSoapHeader($header, $this->namespace);
    }

    /**
     * @param Wsdl\LoginScopeHeader $header
     */
    public function setLoginScopeHeader(Wsdl\LoginScopeHeader $header)
    {
        $this->loginScopeHeader = $this->toSoapHeader($header, $this->namespace);
    }

    /**
     * @param Wsdl\MruHeader $header
     */
    public function setMruHeader(Wsdl\MruHeader $header)
    {
        $this->mruHeader = $this->toSoapHeader($header, $this->namespace);
    }

    /**
     * @param Wsdl\SessionHeader $header
     */
    public function setSessionHeader(Wsdl\SessionHeader $header)
    {
        $this->sessionHeader = $this->toSoapHeader($header, $this->namespace);
        $this->sessionId = $header ? $header->getSessionId() : null;
    }

    /**
     * @param Wsdl\UserTerritoryDeleteHeader $header
     */
    public function setUserTerritoryDeleteHeader(Wsdl\UserTerritoryDeleteHeader $header)
    {
        $this->userTerritoryDeleteHeader = $this->toSoapHeader($header, $this->namespace);
    }

    /**
     * @param Wsdl\QueryOptions $header
     */
    public function setQueryOptions(Wsdl\QueryOptions $header)
    {
        $this->queryHeader = $this->toSoapHeader($header, $this->namespace);
    }

    /**
     * @param Wsdl\AllowFieldTruncationHeader $header
     */
    public function setAllowFieldTruncationHeader(Wsdl\AllowFieldTruncationHeader $header)
    {
        $this->allowFieldTruncationHeader = $this->toSoapHeader($header, $this->namespace);
    }

    /**
     * @param Wsdl\LocaleOptions $header
     */
    public function setLocaleOptions(Wsdl\LocaleOptions $header)
    {
        $this->localeOptions = $this->toSoapHeader($header, $this->namespace);
    }

    /**
     * @param Wsdl\PackageVersionHeader $header
     */
    public function setPackageVersionHeader(Wsdl\PackageVersionHeader $header)
    {
        $this->packageVersionHeader = $this->toSoapHeader($header, $this->namespace);
    }

    public function getSessionId()
    {
        return $this->sessionId;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function getConnection()
    {
        return $this->sforce;
    }

    public function getFunctions()
    {
        return $this->sforce->__getFunctions();
    }

    public function getTypes()
    {
        return $this->sforce->__getTypes();
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

    /**
     * @param null|object $object
     * @param string $namespace
     *
     * @return null|\SoapHeader
     */
    protected function toSoapHeader($object, $namespace)
    {
        if ($object === null) {
            return null;
        }

        return new \SoapHeader(
            $namespace,
            preg_replace('/.*\\\\/', '', get_class($object)),
            \Closure::bind(
                function () {
                    return get_object_vars($this);
                },
                $object,
                $object
            )->__invoke()
        );
    }

    /**
     * @param null|object $object
     * @param null|object $soapResponse
     */
    protected function fromSoapResponse($object, $soapResponse)
    {
        if (!$object || !$soapResponse) {
            return;
        }

        \Closure::bind(
            function () use ($soapResponse) {
                foreach (get_object_vars($soapResponse) as $key => $val) {
                    // TODO what about object properties?

                    $this->$key = $val;
                }
            },
            $object,
            $object
        )->__invoke();
    }

    /**
     * @param array $fields
     *
     * @return string
     */
    protected function _convertToAny($fields)
    {
        $anyString = '';
        foreach ($fields as $key => $value) {
            $anyString = $anyString . '<' . $key . '>' . $value . '</' . $key . '>';
        }

        return $anyString;
    }

    protected function _create($arg)
    {
        $this->setHeaders(static::CALL_CREATE);

        return $this->sforce->create($arg)->result;
    }

    protected function _merge($arg)
    {
        $this->setHeaders(static::CALL_MERGE);

        return $this->sforce->merge($arg)->result;
    }

    protected function _process($arg)
    {
        $this->setHeaders();

        return $this->sforce->process($arg)->result;
    }

    protected function _update($arg)
    {
        $this->setHeaders(static::CALL_UPDATE);

        return $this->sforce->update($arg)->result;
    }

    protected function _upsert($arg)
    {
        $this->setHeaders(static::CALL_UPSERT);

        return $this->sforce->upsert($arg)->result;
    }

    /**
     * @param array $request
     *
     * @return Unknown
     */
    public function sendSingleEmail(array $request)
    {
        $messages = [];
        foreach ($request as $r) {
            $messages[] = new \SoapVar($r, SOAP_ENC_OBJECT, 'SingleEmailMessage', $this->namespace);
        }
        $arg = new \stdClass();
        $arg->messages = $messages;

        return $this->_sendEmail($arg);
    }

    /**
     * @param array $request
     *
     * @return Unknown
     */
    public function sendMassEmail(array $request)
    {
        $messages = [];
        foreach ($request as $r) {
            $messages[] = new \SoapVar($r, SOAP_ENC_OBJECT, 'MassEmailMessage', $this->namespace);
        }
        $arg = new \stdClass();
        $arg->messages = $messages;

        return $this->_sendEmail($arg);
    }

    /**
     * @param $arg
     *
     * @return Unknown
     */
    protected function _sendEmail($arg)
    {
        $this->setHeaders();

        return $this->sforce->sendEmail($arg)->result;
    }

    /**
     * Converts a Lead into an Account, Contact, or (optionally) an Opportunity.
     *
     * @param array $leadConverts Array of LeadConvert
     *
     * @return LeadConvertResult
     */
    public function convertLead($leadConverts)
    {
        $this->setHeaders(static::CALL_CONVERT_LEAD);
        $arg = new \stdClass();
        $arg->leadConverts = $leadConverts;

        return $this->sforce->convertLead($arg);
    }

    /**
     * Deletes one or more new individual objects to your organization's data.
     *
     * @param array $ids Array of fields
     *
     * @return DeleteResult
     */
    public function delete($ids)
    {
        $this->setHeaders(static::CALL_DELETE);
        if (count($ids) > 200) {
            $result = [];
            $chunked_ids = array_chunk($ids, 200);
            foreach ($chunked_ids as $cids) {
                $arg = new \stdClass;
                $arg->ids = $cids;
                $result = array_merge($result, $this->sforce->delete($arg)->result);
            }
        } else {
            $arg = new \stdClass;
            $arg->ids = $ids;
            $result = $this->sforce->delete($arg)->result;
        }

        return $result;
    }

    /**
     * Deletes one or more new individual objects to your organization's data.
     *
     * @param array $ids Array of fields
     *
     * @return DeleteResult
     */
    public function undelete($ids)
    {
        $this->setHeaders(static::CALL_UNDELETE);
        $arg = new \stdClass();
        $arg->ids = $ids;

        return $this->sforce->undelete($arg)->result;
    }

    /**
     * Deletes one or more new individual objects to your organization's data.
     *
     * @param array $ids Array of fields
     *
     * @return DeleteResult
     */
    public function emptyRecycleBin($ids)
    {
        $this->setHeaders();
        $arg = new \stdClass();
        $arg->ids = $ids;

        return $this->sforce->emptyRecycleBin($arg)->result;
    }

    /**
     * Process Submit Request for Approval
     *
     * @param array $processRequestArray
     *
     * @return ProcessResult
     */
    public function processSubmitRequest(array $processRequestArray)
    {
        $arg = new \stdClass();
        $arg->actions = array_map(
            function ($process) {
                return new \SoapVar($process, SOAP_ENC_OBJECT, 'ProcessSubmitRequest', $this->namespace);
            },
            $processRequestArray
        );

        return $this->_process($arg);
    }

    /**
     * Process Work Item Request for Approval
     *
     * @param array $processRequestArray
     *
     * @return ProcessResult
     */
    public function processWorkitemRequest(array $processRequestArray)
    {
        $arg = new \stdClass();
        $arg->actions = array_map(
            function ($process) {
                return new \SoapVar($process, SOAP_ENC_OBJECT, 'ProcessWorkitemRequest', $this->namespace);
            },
            $processRequestArray
        );

        return $this->_process($arg);
    }

    /**
     * Retrieves a list of available objects for your organization's data.
     *
     * @return DescribeGlobalResult
     */
    public function describeGlobal()
    {
        $this->setHeaders(static::CALL_DESCRIBE_GLOBAL);

        return $this->sforce->describeGlobal()->result;
    }

    /**
     * Use describeLayout to retrieve information about the layout (presentation
     * of data to users) for a given object type. The describeLayout call returns
     * metadata about a given page layout, including layouts for edit and
     * display-only views and record type mappings. Note that field-level security
     * and layout editability affects which fields appear in a layout.
     *
     * @param string Type   Object Type
     *
     * @return DescribeLayoutResult
     */
    public function describeLayout($type, array $recordTypeIds = null)
    {
        $this->setHeaders(static::CALL_DESCRIBE_LAYOUT);
        $arg = new \stdClass();
        $arg->sObjectType = new \SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        if (isset($recordTypeIds) && count($recordTypeIds)) {
            $arg->recordTypeIds = $recordTypeIds;
        }

        return $this->sforce->describeLayout($arg)->result;
    }

    /**
     * Describes metadata (field list and object properties) for the specified
     * object.
     *
     * @param string $type Object type
     *
     * @return DescribsSObjectResult
     */
    public function describeSObject($type)
    {
        $this->setHeaders(static::CALL_DESCRIBE_SOBJECT);
        $arg = new \stdClass();
        $arg->sObjectType = new \SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');

        return $this->sforce->describeSObject($arg)->result;
    }

    /**
     * An array-based version of describeSObject; describes metadata (field list
     * and object properties) for the specified object or array of objects.
     *
     * @param array $arrayOfTypes Array of object types.
     *
     * @return DescribsSObjectResult
     */
    public function describeSObjects($arrayOfTypes)
    {
        $this->setHeaders(static::CALL_DESCRIBE_SOBJECTS);

        return $this->sforce->describeSObjects($arrayOfTypes)->result;
    }

    /**
     * The describeTabs call returns information about the standard apps and
     * custom apps, if any, available for the user who sends the call, including
     * the list of tabs defined for each app.
     *
     * @return DescribeTabSetResult
     */
    public function describeTabs()
    {
        $this->setHeaders(static::CALL_DESCRIBE_TABS);

        return $this->sforce->describeTabs()->result;
    }

    /**
     * To enable data categories groups you must enable Answers or Knowledge Articles module in
     * admin panel, after adding category group and assign it to Answers or Knowledge Articles
     *
     * @param string $sObjectType sObject Type
     *
     * @return DescribeDataCategoryGroupResult
     */
    public function describeDataCategoryGroups($sObjectType)
    {
        $this->setHeaders(static::CALL_DESCRIBE_DATA_CATEGORY_GROUPS);
        $arg = new \stdClass();
        $arg->sObjectType = new \SoapVar($sObjectType, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');

        return $this->sforce->describeDataCategoryGroups($arg)->result;
    }

    /**
     * Retrieves available category groups along with their data category structure for objects specified in the request.
     *
     * @param DataCategoryGroupSobjectTypePair $pairs
     * @param bool $topCategoriesOnly Object Type
     *
     * @return DescribeLayoutResult
     */
    public function describeDataCategoryGroupStructures(array $pairs, $topCategoriesOnly)
    {
        $this->setHeaders(static::CALL_DESCRIBE_DATA_CATEGORY_GROUP_STRUCTURES);
        $arg = new \stdClass();
        $arg->pairs = $pairs;
        $arg->topCategoriesOnly = new \SoapVar($topCategoriesOnly, XSD_BOOLEAN, 'boolean', 'http://www.w3.org/2001/XMLSchema');

        return $this->sforce->describeDataCategoryGroupStructures($arg)->result;
    }

    /**
     * Retrieves the list of individual objects that have been deleted within the
     * given timespan for the specified object.
     *
     * @param string $type Ojbect type
     * @param date $startDate Start date
     * @param date $endDate End Date
     *
     * @return GetDeletedResult
     */
    public function getDeleted($type, $startDate, $endDate)
    {
        $this->setHeaders(static::CALL_GET_DELETED);
        $arg = new \stdClass();
        $arg->sObjectType = new \SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        $arg->startDate = $startDate;
        $arg->endDate = $endDate;

        return $this->sforce->getDeleted($arg)->result;
    }

    /**
     * Retrieves the list of individual objects that have been updated (added or
     * changed) within the given timespan for the specified object.
     *
     * @param string $type Ojbect type
     * @param date $startDate Start date
     * @param date $endDate End Date
     *
     * @return GetUpdatedResult
     */
    public function getUpdated($type, $startDate, $endDate)
    {
        $this->setHeaders(static::CALL_GET_UPDATED);
        $arg = new \stdClass();
        $arg->sObjectType = new \SoapVar($type, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        $arg->startDate = $startDate;
        $arg->endDate = $endDate;

        return $this->sforce->getUpdated($arg)->result;
    }

    /**
     * Executes a query against the specified object and returns data that matches
     * the specified criteria.
     *
     * @param String $query Query String
     *
     * @return QueryResult
     */
    public function query($query)
    {
        $this->setHeaders(static::CALL_QUERY);
        $raw = $this->sforce
            ->query(['queryString' => $query])
            ->result;

        return new QueryResult($raw, $this);
    }

    /**
     * Retrieves the next batch of objects from a query.
     *
     * @param QueryLocator $queryLocator Represents the server-side cursor that tracks the current processing location in the query result set.
     *
     * @return QueryResult
     */
    public function queryMore($queryLocator)
    {
        $this->setHeaders(static::CALL_QUERY_MORE);
        $arg = new \stdClass();
        $arg->queryLocator = $queryLocator;
        $raw = $this->sforce->queryMore($arg)->result;

        return new QueryResult($raw, $this);
    }

    /**
     * Retrieves data from specified objects, whether or not they have been deleted.
     *
     * @param String $query Query String
     * @param QueryOptions $queryOptions Batch size limit.  OPTIONAL
     *
     * @return QueryResult
     */
    public function queryAll($query, $queryOptions = null)
    {
        $this->setHeaders(static::CALL_QUERY_ALL);
        $raw = $this->sforce->queryAll([
            'queryString' => $query,
        ])->result;

        return new QueryResult($raw, $this);
    }

    /**
     * Retrieves one or more objects based on the specified object IDs.
     *
     * @param string $fieldList One or more fields separated by commas.
     * @param string $sObjectType Object from which to retrieve data.
     * @param array $ids Array of one or more IDs of the objects to retrieve.
     *
     * @return SObject[]
     */
    public function retrieve($fieldList, $sObjectType, $ids)
    {
        $this->setHeaders(static::CALL_RETRIEVE);
        $arg = new \stdClass();
        $arg->fieldList = $fieldList;
        $arg->sObjectType = new \SoapVar($sObjectType, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        $arg->ids = $ids;

        return $this->sforce->retrieve($arg)->result;
    }

    /**
     * Executes a text search in your organization's data.
     *
     * @param string $searchString Search string that specifies the text expression to search for.
     *
     * @return SearchResult
     */
    public function search($searchString)
    {
        $this->setHeaders(static::CALL_SEARCH);
        $arg = new \stdClass();
        $arg->searchString = new \SoapVar($searchString, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');

        return new SearchResult($this->sforce->search($arg)->result, $this);
    }

    /**
     * Retrieves the current system timestamp (GMT) from the Web service.
     *
     * @return timestamp
     */
    public function getServerTimestamp()
    {
        $this->setHeaders(static::CALL_GET_SERVER_TIMESTAMP);

        return $this->sforce->getServerTimestamp()->result;
    }

    /**
     * @return UserInfo
     */
    public function getUserInfo()
    {
        $this->setHeaders(static::CALL_GET_USER_INFO);

        return $this->sforce->getUserInfo()->result;
    }

    /**
     * Sets the specified user's password to the specified value.
     *
     * @param string $userId ID of the User
     * @param string $password New password
     *
     * @return password
     */
    public function setPassword($userId, $password)
    {
        $this->setHeaders(static::CALL_SET_PASSWORD);
        $arg = new \stdClass();
        $arg->userId = new \SoapVar($userId, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');
        $arg->password = $password;

        return $this->sforce->setPassword($arg);
    }

    /**
     * Changes a user's password to a system-generated value.
     *
     * @param string $userId Id of the User
     *
     * @return password
     */
    public function resetPassword($userId)
    {
        $this->setHeaders(static::CALL_RESET_PASSWORD);
        $arg = new \stdClass();
        $arg->userId = new \SoapVar($userId, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');

        return $this->sforce->resetPassword($arg)->result;
    }


    /**
     * Adds one or more new individual objects to your organization's data.
     *
     * @param SObject[] $sObjects Array of one or more sObjects (up to 200) to create.
     * @param null|string $type
     *
     * @return SaveResult
     */
    abstract public function create($sObjects, $type = null);
}
