<?php

declare(strict_types=1);

namespace muzosh\web_eid_authtoken_validation_php\exceptions;

use Throwable;

/**
 * Thrown when user certificate parsing fails.
 */
// TODO: currently not used, maybe implement it at the same places as in Java library
class UserCertificateParseException extends AuthTokenException
{
    public function __construct(Throwable $cause)
    {
        parent::__construct('Error parsing certificate', $cause);
    }
}