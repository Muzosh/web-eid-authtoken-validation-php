<?php

declare(strict_types=1);

namespace muzosh\web_eid_authtoken_validation_php\exceptions;

/**
 * Thrown when the user certificate has been revoked.
 */
class UserCertificateRevokedException extends AuthTokenException
{
    public function __construct(string $message = null)
    {
        if (is_null($message)) {
            parent::__construct('User certificate has been revoked');
        } else {
            parent::__construct('User certificate has been revoked: '.$message);
        }
    }
}
