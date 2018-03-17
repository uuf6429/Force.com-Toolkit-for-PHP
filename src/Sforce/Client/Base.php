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

use SForce\QueryResult;
use SForce\SearchResult;
use SForce\Soap\AllowFieldTruncationHeader;
use SForce\Soap\AssignmentRuleHeader;
use SForce\Soap\EmailHeader;
use SForce\Soap\LocaleOptions;
use SForce\Soap\LoginScopeHeader;
use SForce\Soap\MruHeader;
use SForce\Soap\PackageVersionHeader;
use SForce\Soap\QueryOptions;
use SForce\Soap\UserTerritoryDeleteHeader;
use SForce\SObject;

abstract class Base
{
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

    public function setCallOptions($header)
    {
        if ($header !== null) {
            $this->callOptions = new \SoapHeader($this->namespace, 'CallOptions', [
                'client' => $header->client,
                'defaultNamespace' => $header->defaultNamespace,
            ]);
        } else {
            $this->callOptions = null;
        }
    }

    /**
     * Login to Salesforce.com and starts a client session.
     *
     * @param string $username Username
     * @param string $password Password
     *
     * @return LoginResult
     */
    public function login($username, $password)
    {
        $this->sforce->__setSoapHeaders(null);
        if ($this->callOptions !== null) {
            $this->sforce->__setSoapHeaders([$this->callOptions]);
        }
        if ($this->loginScopeHeader !== null) {
            $this->sforce->__setSoapHeaders([$this->loginScopeHeader]);
        }
        $result = $this->sforce->login([
            'username' => $username,
            'password' => $password,
        ]);
        $result = $result->result;
        $this->_setLoginHeader($result);

        return $result;
    }

    /**
     * log outs from the salseforce system`
     *
     * @return LogoutResult
     */
    public function logout()
    {
        $this->setHeaders('logout');

        return $this->sforce->logout();
    }

    /**
     *invalidate Sessions from the salseforce system`
     *
     * @return invalidateSessionsResult
     */
    public function invalidateSessions()
    {
        $this->setHeaders('invalidateSessions');
        $this->logout();

        return $this->sforce->invalidateSessions();
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

        if (in_array($call, ['create', 'merge', 'update', 'upsert'], true)) {
            $header = $this->assignmentRuleHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        if ($call === 'login') {
            $header = $this->loginScopeHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        if (in_array($call, ['create', 'resetPassword', 'update', 'upsert'], true)) {
            $header = $this->emailHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        if (in_array($call, ['create', 'merge', 'query', 'retrieve', 'update', 'upsert'], true)) {
            $header = $this->mruHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        if ($call === 'delete') {
            $header = $this->userTerritoryDeleteHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        if (in_array($call, ['query', 'queryMore', 'retrieve'], true)) {
            $header = $this->queryHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        // try to add allowFieldTruncationHeader
        $allowFieldTruncationHeaderCalls = [
            'convertLead',
            'create',
            'merge',
            'process',
            'undelete',
            'update',
            'upsert',
        ];
        if (in_array($call, $allowFieldTruncationHeaderCalls, true)) {
            $header = $this->allowFieldTruncationHeader;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        // try to add localeOptions
        if ($call === 'describeSObject' || $call === 'describeSObjects') {
            $header = $this->localeOptions;
            if ($header !== null) {
                $header_array[] = $header;
            }
        }

        // try to add PackageVersionHeader
        $packageVersionHeaderCalls = [
            'convertLead',
            'create',
            'delete',
            'describeGlobal',
            'describeLayout',
            'describeSObject',
            'describeSObjects',
            'describeSoftphoneLayout',
            'describeTabs',
            'merge',
            'process',
            'query',
            'retrieve',
            'search',
            'undelete',
            'update',
            'upsert',
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
     * @param AssignmentRuleHeader $header
     */
    public function setAssignmentRuleHeader(AssignmentRuleHeader $header)
    {
        if ($header !== null) {
            $this->assignmentRuleHeader = new \SoapHeader($this->namespace, 'AssignmentRuleHeader', [
                'assignmentRuleId' => $header->assignmentRuleId,
                'useDefaultRule' => $header->useDefaultRuleFlag,
            ]);
        } else {
            $this->assignmentRuleHeader = null;
        }
    }

    /**
     * @param EmailHeader $header
     */
    public function setEmailHeader(EmailHeader $header)
    {
        if ($header !== null) {
            $this->emailHeader = new \SoapHeader($this->namespace, 'EmailHeader', [
                'triggerAutoResponseEmail' => $header->triggerAutoResponseEmail,
                'triggerOtherEmail' => $header->triggerOtherEmail,
                'triggerUserEmail' => $header->triggerUserEmail,
            ]);
        } else {
            $this->emailHeader = null;
        }
    }

    /**
     * @param LoginScopeHeader $header
     */
    public function setLoginScopeHeader(LoginScopeHeader $header)
    {
        if ($header !== null) {
            $this->loginScopeHeader = new \SoapHeader($this->namespace, 'LoginScopeHeader', [
                'organizationId' => $header->organizationId,
                'portalId' => $header->portalId,
            ]);
        } else {
            $this->loginScopeHeader = null;
        }
    }

    /**
     * @param MruHeader $header
     */
    public function setMruHeader(MruHeader $header)
    {
        if ($header !== null) {
            $this->mruHeader = new \SoapHeader($this->namespace, 'MruHeader', [
                'updateMru' => $header->updateMruFlag,
            ]);
        } else {
            $this->mruHeader = null;
        }
    }

    public function setSessionHeader($id)
    {
        if ($id !== null) {
            $this->sessionHeader = new \SoapHeader($this->namespace, 'SessionHeader', [
                'sessionId' => $id,
            ]);
            $this->sessionId = $id;
        } else {
            $this->sessionHeader = null;
            $this->sessionId = null;
        }
    }

    /**
     * @param UserTerritoryDeleteHeader $header
     */
    public function setUserTerritoryDeleteHeader(UserTerritoryDeleteHeader $header)
    {
        if ($header !== null) {
            $this->userTerritoryDeleteHeader = new \SoapHeader($this->namespace, 'UserTerritoryDeleteHeader', [
                'transferToUserId' => $header->transferToUserId,
            ]);
        } else {
            $this->userTerritoryDeleteHeader = null;
        }
    }

    /**
     * @param QueryOptions $header
     */
    public function setQueryOptions(QueryOptions $header)
    {
        if ($header !== null) {
            $this->queryHeader = new \SoapHeader($this->namespace, 'QueryOptions', [
                'batchSize' => $header->batchSize,
            ]);
        } else {
            $this->queryHeader = null;
        }
    }

    /**
     * @param AllowFieldTruncationHeader $header
     */
    public function setAllowFieldTruncationHeader(AllowFieldTruncationHeader $header)
    {
        if ($header !== null) {
            $this->allowFieldTruncationHeader = new \SoapHeader(
                $this->namespace,
                'AllowFieldTruncationHeader',
                [
                    'allowFieldTruncation' => $header->allowFieldTruncation,
                ]
            );
        } else {
            $this->allowFieldTruncationHeader = null;
        }
    }

    /**
     * @param LocaleOptions $header
     */
    public function setLocaleOptions(LocaleOptions $header)
    {
        if ($header !== null) {
            $this->localeOptions = new \SoapHeader(
                $this->namespace,
                'LocaleOptions',
                [
                    'language' => $header->language,
                ]
            );
        } else {
            $this->localeOptions = null;
        }
    }

    /**
     * @param PackageVersionHeader $header
     */
    public function setPackageVersionHeader(PackageVersionHeader $header)
    {
        if ($header !== null) {
            $headerData = ['packageVersions' => []];

            foreach ($header->packageVersions as $key => $hdrElem) {
                $headerData['packageVersions'][] = [
                    'majorNumber' => $hdrElem->majorNumber,
                    'minorNumber' => $hdrElem->minorNumber,
                    'namespace' => $hdrElem->namespace,
                ];
            }

            $this->packageVersionHeader = new \SoapHeader(
                $this->namespace,
                'PackageVersionHeader',
                $headerData
            );
        } else {
            $this->packageVersionHeader = null;
        }
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
        $this->setHeaders('create');

        return $this->sforce->create($arg)->result;
    }

    protected function _merge($arg)
    {
        $this->setHeaders('merge');

        return $this->sforce->merge($arg)->result;
    }

    protected function _process($arg)
    {
        $this->setHeaders();

        return $this->sforce->process($arg)->result;
    }

    protected function _update($arg)
    {
        $this->setHeaders('update');

        return $this->sforce->update($arg)->result;
    }

    protected function _upsert($arg)
    {
        $this->setHeaders('upsert');

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
        $this->setHeaders('convertLead');
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
        $this->setHeaders('delete');
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
        $this->setHeaders('undelete');
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
        $this->setHeaders('describeGlobal');

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
        $this->setHeaders('describeLayout');
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
        $this->setHeaders('describeSObject');
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
        $this->setHeaders('describeSObjects');

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
        $this->setHeaders('describeTabs');

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
        $this->setHeaders('describeDataCategoryGroups');
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
        $this->setHeaders('describeDataCategoryGroupStructures');
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
        $this->setHeaders('getDeleted');
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
        $this->setHeaders('getUpdated');
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
        $this->setHeaders('query');
        $raw = $this->sforce
            ->query(['queryString' => $query])
            ->result;
        $queryResult = new QueryResult($raw);
        $queryResult->setSf($this); // Dependency Injection

        return $queryResult;
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
        $this->setHeaders('queryMore');
        $arg = new \stdClass();
        $arg->queryLocator = $queryLocator;
        $raw = $this->sforce->queryMore($arg)->result;
        $queryResult = new QueryResult($raw);
        $queryResult->setSf($this); // Dependency Injection

        return $queryResult;
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
        $this->setHeaders('queryAll');
        $raw = $this->sforce->queryAll([
            'queryString' => $query,
        ])->result;
        $queryResult = new QueryResult($raw);
        $queryResult->setSf($this); // Dependency Injection

        return $queryResult;
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
        $this->setHeaders('retrieve');
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
        $this->setHeaders('search');
        $arg = new \stdClass();
        $arg->searchString = new \SoapVar($searchString, XSD_STRING, 'string', 'http://www.w3.org/2001/XMLSchema');

        return new SforceSearchResult($this->sforce->search($arg)->result);
    }

    /**
     * Retrieves the current system timestamp (GMT) from the Web service.
     *
     * @return timestamp
     */
    public function getServerTimestamp()
    {
        $this->setHeaders('getServerTimestamp');

        return $this->sforce->getServerTimestamp()->result;
    }

    /**
     * @return UserInfo
     */
    public function getUserInfo()
    {
        $this->setHeaders('getUserInfo');

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
        $this->setHeaders('setPassword');
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
        $this->setHeaders('resetPassword');
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
