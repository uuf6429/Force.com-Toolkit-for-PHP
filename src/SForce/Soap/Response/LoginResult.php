<?php

namespace SForce\Soap\Response;

use SForce\ValueObject;

/**
 * @property string $metadataServerUrl
 * @property bool $passwordExpired
 * @property bool $sandbox
 * @property string $serverUrl
 * @property string $sessionId
 * @property string $userId
 * @property LoginResultUserInfo $userInfo
 */
class LoginResult extends ValueObject
{
    /**
     * @inheritdoc
     */
    public function __construct(array $data = null)
    {
        $data['userInfo'] = new LoginResultUserInfo((array) $data['userInfo']);

        parent::__construct($data);
    }
}
