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

use muzosh\web_eid_authtoken_validation_php\validator\ocsp\service\AiaOcspService;
use muzosh\web_eid_authtoken_validation_php\validator\ocsp\service\AiaOcspServiceConfiguration;
use muzosh\web_eid_authtoken_validation_php\validator\ocsp\service\DesignatedOcspService;
use muzosh\web_eid_authtoken_validation_php\validator\ocsp\service\DesignatedOcspServiceConfiguration;
use muzosh\web_eid_authtoken_validation_php\validator\ocsp\service\OcspService;
use phpseclib3\File\X509;

class OcspServiceProvider
{
    private ?DesignatedOcspService $designatedOcspService;
    private AiaOcspServiceConfiguration $aiaOcspServiceConfiguration;

    public function __construct(?DesignatedOcspServiceConfiguration $designatedOcspServiceConfiguration, AiaOcspServiceConfiguration $aiaOcspServiceConfiguration)
    {
        $this->designatedOcspService = !is_null($designatedOcspServiceConfiguration) ?
            new DesignatedOcspService($designatedOcspServiceConfiguration)
            : null;
        $this->aiaOcspServiceConfiguration = $aiaOcspServiceConfiguration;
    }

    /**
     * A static factory method that returns either the designated or AIA OCSP service instance depending on whether
     * the designated OCSP service is configured and supports the issuer of the certificate.
     *
     * @param X509 $certificate subject certificate that is to be checked with OCSP
     *
     * @throws AuthTokenException           when AIA URL is not found in certificate
     * @throws CertificateEncodingException when certificate is invalid
     */
    public function getService(X509 $certificate): OcspService
    {
        if (!is_null($this->designatedOcspService) && $this->designatedOcspService->supportsIssuerOf($certificate)) {
            return $this->designatedOcspService;
        }

        return new AiaOcspService($this->aiaOcspServiceConfiguration, $certificate);
    }
}
