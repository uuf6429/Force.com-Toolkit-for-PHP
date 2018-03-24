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

namespace SForce\Meta;

class CustomObject
{
    const DEPLOYMENT_STATUS_IN_DEVELOPMENT = 'InDevelopment';
    const DEPLOYMENT_STATUS_DEPLOYED = 'Deployed';

    const GENDER_NEUTER = 'Neuter';
    const GENDER_MASCULINE = 'Masculine';
    const GENDER_FEMININE = 'Feminine';

    const SHARING_MODEL_PRIVATE = 'Private';
    const SHARING_MODEL_READ = 'Read';
    const SHARING_MODEL_READWRITE = 'ReadWrite';

    const STARTS_WITH_CONSONANT = 'Consonant';
    const STARTS_WITH_VOWEL = 'Vowel';
    const STARTS_WITH_SPECIAL = 'Special';

    public $startsWith;
    public $sharingModel;
    public $pluralLabel;
    public $nameField;
    public $label;
    public $household;
    public $gender;
    public $fullName;
    public $fields;
    public $enableReports;
    public $enableHistory;
    public $enableDivisions;
    public $enableActivities;
    public $description;
    public $deploymentStatus;

    /**
     * @param string $deploymentStatus Value from self::DEPLOYMENT_STATUS_* constants.
     */
    public function setDeploymentStatus($deploymentStatus)
    {
        $this->deploymentStatus = $deploymentStatus;
    }

    public function setDescription($description)
    {
        $this->description = $description;
    }

    public function setEnableActivities($enableActivities)
    {
        $this->enableActivities = $enableActivities;
    }

    public function setEnableDivisions($enableDivisions)
    {
        $this->enableDivisions = $enableDivisions;
    }

    public function setEnableHistory($enableHistory)
    {
        $this->enableHistory = $enableHistory;
    }

    public function setEnableReports($enableReports)
    {
        $this->enableReports = $enableReports;
    }

    public function setFields($fields)
    {
        $this->fields = $fields;
    }

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    /**
     * @param string $gender Value from self::GENDER_* constants.
     */
    public function setGender($gender)
    {
        $this->gender = $gender;
    }

    public function setHousehold($household)
    {
        $this->household = $household;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function setNameField($nameField)
    {
        $this->nameField = $nameField;
    }

    public function setPluralLabel($pluralLabel)
    {
        $this->pluralLabel = $pluralLabel;
    }

    /**
     * @param string $sharingModel Value from self::SHARING_MODEL_* constants.
     */
    public function setSharingModel($sharingModel)
    {
        $this->sharingModel = $sharingModel;
    }

    /**
     * @param string $startsWith Value from self::STARTS_WITH_* constants.
     */
    public function setStartsWith($startsWith)
    {
        $this->startsWith = $startsWith;
    }
}
