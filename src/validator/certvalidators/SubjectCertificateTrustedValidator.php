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

namespace muzosh\web_eid_authtoken_validation_php\validator\certvalidators;

use muzosh\web_eid_authtoken_validation_php\certificate\CertificateValidator;
use muzosh\web_eid_authtoken_validation_php\exceptions\CertificateNotTrustedException;
use muzosh\web_eid_authtoken_validation_php\util\TrustedCertificates;
use muzosh\web_eid_authtoken_validation_php\util\WebEidLogger;
use phpseclib3\Exception\UnsupportedAlgorithmException;
use phpseclib3\File\X509;
use Psr\Log\InvalidArgumentException;
use RangeException;
use RuntimeException;
use Throwable;
use TypeError;

final class SubjectCertificateTrustedValidator implements SubjectCertificateValidator
{
    private $logger;

    private TrustedCertificates $trustedCertificates;
    private X509 $subjectCertificateIssuerCertificate;

    public function __construct(
        TrustedCertificates $trustedCertificates
    ) {
        $this->logger = WebEidLogger::getLogger(
            self::class
        );
        $this->trustedCertificates = $trustedCertificates;
    }

    /**
     * Validates that the user certificate from the authentication token is signed by a trusted certificate authority.
     *
     * @throws RangeException
     * @throws TypeError
     * @throws RuntimeException
     * @throws UnsupportedAlgorithmException
     * @throws CertificateNotTrustedException
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function validate(X509 $subjectCertificate): void
    {
        $this->subjectCertificateIssuerCertificate = CertificateValidator::validateIsSignedByTrustedCertificate(
            $subjectCertificate,
            $this->trustedCertificates
        );
        $this->logger->debug('Subject certificate is signed by a trusted CA');
    }

    public function getSubjectCertificateIssuerCertificate(): X509
    {
        return $this->subjectCertificateIssuerCertificate;
    }
}
