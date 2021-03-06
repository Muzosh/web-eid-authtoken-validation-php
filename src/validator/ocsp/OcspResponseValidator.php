<?php

/* The MIT License (MIT)
*
* Copyright (c) 2022 Petr Muzikant <pmuzikant@email.cz>
*
* > Permission is hereby granted, free of charge, to any person obtaining a copy
* > of this software and associated documentation files (the "Software"), to deal
* > in the Software without restriction, including without limitation the rights
* > to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* > copies of the Software, and to permit persons to whom the Software is
* > furnished to do so, subject to the following conditions:
* >
* > The above copyright notice and this permission notice shall be included in
* > all copies or substantial portions of the Software.
* >
* > THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* > IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* > FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* > AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* > LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* > OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* > THE SOFTWARE.
*/

declare(strict_types=1);

namespace muzosh\web_eid_authtoken_validation_php\validator\ocsp;

use BadFunctionCallException;
use DateInterval;
use DateTime;
use LengthException;
use muzosh\web_eid_authtoken_validation_php\exceptions\OCSPCertificateException;
use muzosh\web_eid_authtoken_validation_php\exceptions\UserCertificateOCSPCheckFailedException;
use muzosh\web_eid_authtoken_validation_php\exceptions\UserCertificateRevokedException;
use muzosh\web_eid_authtoken_validation_php\ocsp\BasicResponseObject;
use muzosh\web_eid_authtoken_validation_php\util\DateAndTime;
use phpseclib3\Exception\InconsistentSetupException;
use phpseclib3\Exception\InsufficientSetupException;
use phpseclib3\Exception\NoKeyLoadedException;
use phpseclib3\File\X509;
use RuntimeException;
use TypeError;

final class OcspResponseValidator
{
    /**
     * Indicates that a X.509 Certificates corresponding private key may be used by an authority to sign OCSP responses.
     * <p>
     * https://oidref.com/1.3.6.1.5.5.7.3.9.
     */
    private const OCSP_SIGNING = 'id-kp-OCSPSigning';

    // 15 mins = 900s
    private const ALLOWED_TIME_SKEW_SECONDS = 900;

    private function __construct()
    {
        throw new BadFunctionCallException('Utility class');
    }

    /**
     * @throws InsufficientSetupException
     * @throws LengthException
     * @throws TypeError
     * @throws OCSPCertificateException
     */
    public static function validateHasSigningExtension(X509 $certificate): void
    {
        if (!$certificate->getExtension('id-ce-extKeyUsage') || !in_array(self::OCSP_SIGNING, $certificate->getExtension('id-ce-extKeyUsage'))) {
            throw new OCSPCertificateException('Certificate '.$certificate->getSubjectDN(X509::DN_STRING).
                ' does not contain the key usage extension for OCSP response signing');
        }
    }

    /**
     * @throws NoKeyLoadedException
     * @throws InconsistentSetupException
     * @throws OCSPCertificateException
     * @throws RuntimeException
     * @throws UserCertificateOCSPCheckFailedException
     */
    public static function validateResponseSignature(BasicResponseObject $basicResponse, X509 $responderCert): void
    {
        // get public key from responder certificate in order to verify signature on response
        $publicKey = $responderCert->getPublicKey()->withHash($basicResponse->getSignatureAlgorithm());

        // verify response data
        $encodedTbsResponseData = $basicResponse->getEncodedResponseData();
        $signature = $basicResponse->getSignature();

        if (!$publicKey->verify($encodedTbsResponseData, $signature)) {
            throw new UserCertificateOCSPCheckFailedException('OCSP response signature is invalid');
        }
    }

    /**
     * @throws UserCertificateOCSPCheckFailedException
     */
    public static function validateCertificateStatusUpdateTime(array $certStatusResponse, DateTime $producedAt): void
    {
        // From RFC 2560, https://www.ietf.org/rfc/rfc2560.txt:
        // 4.2.2.  Notes on OCSP Responses
        // 4.2.2.1.  Time
        //   Responses whose nextUpdate value is earlier than
        //   the local system time value SHOULD be considered unreliable.
        //   Responses whose thisUpdate time is later than the local system time
        //   SHOULD be considered unreliable.
        //   If nextUpdate is not set, the responder is indicating that newer
        //   revocation information is available all the time.
        $notAllowedBefore = (clone $producedAt)->sub(new DateInterval('PT'.self::ALLOWED_TIME_SKEW_SECONDS.'S'));
        $notAllowedAfter = (clone $producedAt)->add(new DateInterval('PT'.self::ALLOWED_TIME_SKEW_SECONDS.'S'));

        $thisUpdate = new DateTime($certStatusResponse['thisUpdate']);
        $nextUpdate = isset($certStatusResponse['nextUpdate']) ? new DateTime($certStatusResponse['nextUpdate']) : null;

        if ($notAllowedAfter < $thisUpdate
            || $notAllowedBefore > (!is_null($nextUpdate) ? $nextUpdate : $thisUpdate)) {
            throw new UserCertificateOCSPCheckFailedException('Certificate status update time check failed: '.
                'notAllowedBefore: '.DateAndTime::toUtcString($notAllowedBefore).
                ', notAllowedAfter: '.DateAndTime::toUtcString($notAllowedAfter).
                ', thisUpdate: '.DateAndTime::toUtcString($thisUpdate).
                ', nextUpdate: '.DateAndTime::toUtcString($nextUpdate));
        }
    }

    /**
     * @throws UserCertificateRevokedException
     */
    public static function validateSubjectCertificateStatus(array $certStatusResponse): void
    {
        if (isset($certStatusResponse['certStatus']['good'])) {
            return;
        }
        if (isset($certStatusResponse['certStatus']['revoked'])) {
            $revokedStatus = $certStatusResponse['certStatus']['revoked'];

            throw (isset($revokedStatus['revokedReason']) ?
                new UserCertificateRevokedException('Revocation reason: '.$revokedStatus['revokedReason']) :
                new UserCertificateRevokedException());
        }
        if (isset($certStatusResponse['certStatus']['unknown'])) {
            throw new UserCertificateRevokedException('Unknown status');
        }

        throw new UserCertificateRevokedException('Status is neither good, revoked nor unknown');
    }
}
