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

namespace SForce\Email;

class SingleEmailMessage extends Email
{
    public $bccAddresses;
    public $ccAddresses;
    public $charset;
    public $htmlBody;
    public $orgWideEmailAddressId;
    public $plainTextBody;
    public $targetObjectId;
    public $templateId;
    public $toAddresses;
    public $whatId;
    public $fileAttachments;
    public $documentAttachments;

    public function setBccAddresses($addresses)
    {
        $this->bccAddresses = $addresses;
    }

    public function setCcAddresses($addresses)
    {
        $this->ccAddresses = $addresses;
    }

    public function setCharset($charset)
    {
        $this->charset = $charset;
    }

    public function setHtmlBody($htmlBody)
    {
        $this->htmlBody = $htmlBody;
    }

    public function setOrgWideEmailAddressId($orgWideEmailAddressId)
    {
        $this->orgWideEmailAddressId = $orgWideEmailAddressId;
    }

    public function setPlainTextBody($plainTextBody)
    {
        $this->plainTextBody = $plainTextBody;
    }

    public function setTargetObjectId($targetObjectId)
    {
        $this->targetObjectId = $targetObjectId;
    }

    public function setTemplateId($templateId)
    {
        $this->templateId = $templateId;
    }

    public function setToAddresses($array)
    {
        $this->toAddresses = $array;
    }

    public function setWhatId($whatId)
    {
        $this->whatId = $whatId;
    }

    public function setFileAttachments($array)
    {
        $this->fileAttachments = $array;
    }

    public function setDocumentAttachments($array)
    {
        $this->documentAttachments = $array;
    }
}
