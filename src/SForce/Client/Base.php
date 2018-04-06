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
     * @var Wsdl\SforceService
     */
    protected $sforce;
    protected $sessionId;
    protected $location;
    protected $version = '27.0'; // TODO autodetect and/or remove this

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
        $soapOptions = array_merge(
            [
                'user_agent' => "salesforce-toolkit-php/{$this->version}",
                'encoding' => 'utf-8',
                'trace' => 1,
                'features' => SOAP_SINGLE_ELEMENT_ARRAYS,
                'compression' => SOAP_COMPRESSION_ACCEPT | SOAP_COMPRESSION_GZIP,
                'classmap' => [
                    'sObject' => SObject::class,
                    'QueryResult' => QueryResult::class,
                ],
            ],
            $soapOptions
        );

        if ($proxy !== null) {
            $soapOptions = array_merge($soapOptions, $proxy->toArray());
        }

        $this->sforce = $this->createSoapClient($wsdl, $soapOptions);

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
        $result = $this->sforce
            ->login(new Wsdl\login($username, $password))
            ->getResult();

        $this->setLoginHeader($result);

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

        return $this->sforce->logout(new Wsdl\logout());
    }

    /**
     * Invalidates sessions from the Salseforce.com system
     *
     * @return Wsdl\invalidateSessionsResponse
     */
    public function invalidateSessions()
    {
        $this->setHeaders('invalidateSessions');

        $this->logout();

        return $this->sforce->invalidateSessions(new Wsdl\invalidateSessions());
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

        if ($this->sessionHeader === null) {
            throw new NotConnectedException('Session header has not been set.');
        }

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
     * @param Wsdl\SingleEmailMessage[] $messages
     *
     * @return Wsdl\SendEmailResult
     *
     * @deprecated Use sendEmail() directly.
     */
    public function sendSingleEmail(array $messages)
    {
        return $this->sendEmail($messages);
    }

    /**
     * @param Wsdl\MassEmailMessage[] $messages
     *
     * @return Wsdl\SendEmailResult
     *
     * @deprecated Use sendEmail() directly.
     */
    public function sendMassEmail(array $messages)
    {
        return $this->sendEmail($messages);
    }

    /**
     * @param Wsdl\Email[] $messages
     *
     * @return Wsdl\SendEmailResult
     */
    public function sendEmail(array $messages)
    {
        $this->setHeaders();

        return $this->sforce
            ->sendEmail(new Wsdl\sendEmail($messages))
            ->getResult();
    }

    /**
     * Converts a Lead into an Account, Contact, or (optionally) an Opportunity.
     *
     * @param array $leadConverts Array of LeadConvert
     *
     * @return Wsdl\LeadConvertResult
     */
    public function convertLead($leadConverts)
    {
        $this->setHeaders(static::CALL_CONVERT_LEAD);

        return $this->sforce
            ->convertLead(new Wsdl\convertLead($leadConverts))
            ->getResult();
    }

    /**
     * Deletes one or more new individual objects to your organization's data.
     *
     * @param array $ids Array of fields
     *
     * @return Wsdl\DeleteResult
     */
    public function delete($ids)
    {
        $this->setHeaders(static::CALL_DELETE);

        if (count($ids) > 200) {
            $idChunks = array_chunk($ids, 200);
            $success = true;
            $errors = [];

            foreach ($idChunks as $idChunk) {
                $result = $this->sforce
                    ->delete(new Wsdl\delete($idChunk))
                    ->getResult();

                $errors[] = $result->getErrors();
                $success &= $result->getSuccess();
            }

            return (new Wsdl\DeleteResult())
                ->setSuccess($success)
                ->setErrors(array_merge(...$errors));
        }

        return $this->sforce
            ->delete(new Wsdl\delete($ids))
            ->getResult();
    }

    /**
     * Deletes one or more new individual objects to your organization's data.
     *
     * @param array $ids Array of fields
     *
     * @return Wsdl\UndeleteResult
     */
    public function undelete($ids)
    {
        $this->setHeaders(static::CALL_UNDELETE);

        return $this->sforce
            ->undelete(new Wsdl\undelete($ids))
            ->getResult();
    }

    /**
     * Deletes one or more new individual objects to your organization's data.
     *
     * @param array $ids Array of fields
     *
     * @return Wsdl\EmptyRecycleBinResult
     */
    public function emptyRecycleBin($ids)
    {
        $this->setHeaders();

        return $this->sforce
            ->emptyRecycleBin(new Wsdl\emptyRecycleBin($ids))
            ->getResult();
    }

    /**
     * Process Submit Request for Approval
     *
     * @param Wsdl\ProcessSubmitRequest[] $processRequests
     *
     * @return Wsdl\ProcessResult
     *
     * @deprecated Use processRequests() directly.
     */
    public function processSubmitRequest(array $processRequests)
    {
        return $this->processRequests($processRequests);
    }

    /**
     * Process Work Item Request for Approval
     *
     * @param Wsdl\ProcessWorkitemRequest[] $processRequests
     *
     * @return Wsdl\ProcessResult
     *
     * @deprecated Use processRequests() directly.
     */
    public function processWorkitemRequest(array $processRequests)
    {
        return $this->processRequests($processRequests);
    }

    /**
     * Process requests.
     *
     * @param Wsdl\ProcessRequest[] $processRequests
     *
     * @return Wsdl\ProcessResult
     */
    public function processRequests(array $processRequests)
    {
        $this->setHeaders();

        return $this->sforce
            ->process(new Wsdl\process($processRequests))
            ->getResult();
    }

    /**
     * Retrieves a list of available objects for your organization's data.
     *
     * @return Wsdl\DescribeGlobalResult
     */
    public function describeGlobal()
    {
        $this->setHeaders(static::CALL_DESCRIBE_GLOBAL);

        return $this->sforce
            ->describeGlobal(new Wsdl\describeGlobal())
            ->getResult();
    }

    /**
     * Use describeLayout to retrieve information about the layout (presentation
     * of data to users) for a given object type. The describeLayout call returns
     * metadata about a given page layout, including layouts for edit and
     * display-only views and record type mappings. Note that field-level security
     * and layout editability affects which fields appear in a layout.
     *
     * @param string $type Object Type
     * @param array $recordTypeIds
     *
     * @return Wsdl\DescribeLayoutResult
     */
    public function describeLayout($type, array $recordTypeIds = [])
    {
        $this->setHeaders(static::CALL_DESCRIBE_LAYOUT);

        return $this->sforce
            ->describeLayout(new Wsdl\describeLayout($type, $recordTypeIds))
            ->getResult();
    }

    /**
     * Describes metadata (field list and object properties) for the specified
     * object.
     *
     * @param string $type Object type
     *
     * @return Wsdl\DescribeSObjectResult
     */
    public function describeSObject($type)
    {
        $this->setHeaders(static::CALL_DESCRIBE_SOBJECT);

        return $this->sforce
            ->describeSObject(new Wsdl\describeSObject($type))
            ->getResult();
    }

    /**
     * An array-based version of describeSObject; describes metadata (field list
     * and object properties) for the specified object or array of objects.
     *
     * @param array $arrayOfTypes Array of object types.
     *
     * @return Wsdl\DescribeSObjectResult
     */
    public function describeSObjects($arrayOfTypes)
    {
        $this->setHeaders(static::CALL_DESCRIBE_SOBJECTS);

        return $this->sforce
            ->describeSObjects(new Wsdl\describeSObjects($arrayOfTypes))
            ->getResult();
    }

    /**
     * The describeTabs call returns information about the standard apps and
     * custom apps, if any, available for the user who sends the call, including
     * the list of tabs defined for each app.
     *
     * @return Wsdl\DescribeTabSetResult
     */
    public function describeTabs()
    {
        $this->setHeaders(static::CALL_DESCRIBE_TABS);

        return $this->sforce
            ->describeTabs(new Wsdl\describeTabs())
            ->getResult();
    }

    /**
     * To enable data categories groups you must enable Answers or Knowledge Articles module in
     * admin panel, after adding category group and assign it to Answers or Knowledge Articles
     *
     * @param string $sObjectType sObject Type
     *
     * @return Wsdl\DescribeDataCategoryGroupResult
     */
    public function describeDataCategoryGroups($sObjectType)
    {
        $this->setHeaders(static::CALL_DESCRIBE_DATA_CATEGORY_GROUPS);

        return $this->sforce
            ->describeDataCategoryGroups(new Wsdl\describeDataCategoryGroups($sObjectType))
            ->getResult();
    }

    /**
     * Retrieves available category groups along with their data category structure for objects specified in the request.
     *
     * @param Wsdl\DataCategoryGroupSobjectTypePair[] $pairs
     * @param bool $topCategoriesOnly Object Type
     *
     * @return Wsdl\DescribeDataCategoryGroupStructureResult
     */
    public function describeDataCategoryGroupStructures(array $pairs, $topCategoriesOnly)
    {
        $this->setHeaders(static::CALL_DESCRIBE_DATA_CATEGORY_GROUP_STRUCTURES);

        return $this->sforce
            ->describeDataCategoryGroupStructures(
                new Wsdl\describeDataCategoryGroupStructures($pairs, $topCategoriesOnly)
            )
            ->getResult();
    }

    /**
     * Retrieves the list of individual objects that have been deleted within the
     * given timespan for the specified object.
     *
     * @param string $type Object type
     * @param \DateTime $startDate Start date
     * @param \DateTime $endDate End Date
     *
     * @return Wsdl\GetDeletedResult
     */
    public function getDeleted($type, \DateTime $startDate, \DateTime $endDate)
    {
        $this->setHeaders(static::CALL_GET_DELETED);

        return $this->sforce
            ->getDeleted(new Wsdl\getDeleted($type, $startDate, $endDate))
            ->getResult();
    }

    /**
     * Retrieves the list of individual objects that have been updated (added or
     * changed) within the given timespan for the specified object.
     *
     * @param string $type Object type
     * @param \DateTime $startDate Start date
     * @param \DateTime $endDate End Date
     *
     * @return Wsdl\GetUpdatedResult
     */
    public function getUpdated($type, \DateTime $startDate, \DateTime $endDate)
    {
        $this->setHeaders(static::CALL_GET_UPDATED);

        return $this->sforce
            ->getUpdated(new Wsdl\getUpdated($type, $startDate, $endDate))
            ->getResult();
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

        return $this->sforce
            ->query(new Wsdl\query($query))
            ->getResult()
            ->setClient($this);
    }

    /**
     * Retrieves the next batch of objects from a query.
     *
     * @param string $queryLocator Represents the server-side cursor that tracks the current processing location in the query result set.
     *
     * @return QueryResult
     */
    public function queryMore($queryLocator)
    {
        $this->setHeaders(static::CALL_QUERY_MORE);

        return $this->sforce
            ->queryMore(new Wsdl\queryMore($queryLocator))
            ->getResult()
            ->setClient($this);
    }

    /**
     * Retrieves data from specified objects, whether or not they have been deleted.
     *
     * @param string $queryString Query String
     *
     * @return QueryResult
     */
    public function queryAll($queryString)
    {
        $this->setHeaders(static::CALL_QUERY_ALL);

        return $this->sforce
            ->queryAll(new Wsdl\queryAll($queryString))
            ->getResult()
            ->setClient($this);
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

        $result = $this->sforce
            ->retrieve(new Wsdl\retrieve($fieldList, $sObjectType, $ids))
            ->getResult();

        return is_array($result) ? $result : [$result];
    }

    /**
     * Executes a text search in your organization's data.
     *
     * @param string $searchString Search string that specifies the text expression to search for.
     *
     * @return Wsdl\SearchResult
     */
    public function search($searchString)
    {
        $this->setHeaders(static::CALL_SEARCH);

        return $this->sforce
            ->search(new Wsdl\search($searchString))
            ->getResult();
    }

    /**
     * Retrieves the current system timestamp (GMT) from the Web service.
     *
     * @return Wsdl\GetServerTimestampResult
     */
    public function getServerTimestamp()
    {
        $this->setHeaders(static::CALL_GET_SERVER_TIMESTAMP);

        return $this->sforce
            ->getServerTimestamp(new Wsdl\getServerTimestamp())
            ->getResult();
    }

    /**
     * @return Wsdl\GetUserInfoResult
     */
    public function getUserInfo()
    {
        $this->setHeaders(static::CALL_GET_USER_INFO);

        return $this->sforce
            ->getUserInfo(new Wsdl\getUserInfo())
            ->getResult();
    }

    /**
     * Sets the specified user's password to the specified value.
     *
     * @param string $userId ID of the User
     * @param string $password New password
     *
     * @return Wsdl\SetPasswordResult
     */
    public function setPassword($userId, $password)
    {
        $this->setHeaders(static::CALL_SET_PASSWORD);

        return $this->sforce
            ->setPassword(new Wsdl\setPassword($userId, $password))
            ->getResult();
    }

    /**
     * Changes a user's password to a system-generated value.
     *
     * @param string $userId Id of the User
     *
     * @return Wsdl\ResetPasswordResult
     */
    public function resetPassword($userId)
    {
        $this->setHeaders(static::CALL_RESET_PASSWORD);

        return $this->sforce
            ->resetPassword(new Wsdl\resetPassword($userId))
            ->getResult();
    }


    /**
     * Adds one or more new individual objects to your organization's data.
     *
     * @param SObject[] $sObjects Array of one or more sObjects (up to 200) to create.
     * @param null|string $type
     *
     * @return Wsdl\SaveResult
     */
    abstract public function create($sObjects, $type = null);

    /**
     * @param string $wsdl
     * @param array $options
     *
     * @return Wsdl\SforceService
     */
    protected function createSoapClient($wsdl, $options)
    {
        return new Wsdl\SforceService($options, $wsdl);
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

        /** @noinspection ImplicitMagicMethodCallInspection */
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
     * Specifies the session ID returned from the login server after a successful login.
     *
     * @param Wsdl\LoginResult $loginResult
     */
    protected function setLoginHeader(Wsdl\LoginResult $loginResult)
    {
        $this->sessionId = $loginResult->getSessionId();
        $this->setSessionHeader(new Wsdl\SessionHeader($this->sessionId));
        $serverURL = $loginResult->getServerUrl();
        $this->setEndpoint($serverURL);
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

        return $this->sforce->create($arg)->getResult();
    }

    protected function _merge($arg)
    {
        $this->setHeaders(static::CALL_MERGE);

        return $this->sforce->merge($arg)->getResult();
    }

    protected function _update($arg)
    {
        $this->setHeaders(static::CALL_UPDATE);

        return $this->sforce->update($arg)->getResult();
    }

    protected function _upsert($arg)
    {
        $this->setHeaders(static::CALL_UPSERT);

        return $this->sforce->upsert($arg)->getResult();
    }
}
