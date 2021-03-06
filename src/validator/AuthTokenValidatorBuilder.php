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

namespace muzosh\web_eid_authtoken_validation_php\validator;

use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException as GlobalInvalidArgumentException;
use Monolog\Logger;
use muzosh\web_eid_authtoken_validation_php\util\WebEidLogger;
use muzosh\web_eid_authtoken_validation_php\util\X509Array;
use muzosh\web_eid_authtoken_validation_php\validator\ocsp\service\DesignatedOcspServiceConfiguration;
use phpseclib3\File\X509;
use Psr\Log\InvalidArgumentException;
use Throwable;

class AuthTokenValidatorBuilder
{
    private Logger $logger;
    private AuthTokenValidationConfiguration $configuration;

    public function __construct()
    {
        $this->logger = WebEidLogger::getLogger(self::class);
        $this->configuration = new AuthTokenValidationConfiguration();
    }

    /**
     * Sets the expected site origin, i.e. the domain that the application is running on.
     *
     * @param Uri $origin origin URL as defined in MDN,
     *                    in the form of {@code <scheme> "://" <hostname> [ ":" <port> ]}
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/API/Location/origin MDN
     *
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function withSiteOrigin(URI $origin): AuthTokenValidatorBuilder
    {
        $this->configuration->setSiteOrigin($origin);
        $this->logger->debug('Origin set to '.$this->configuration->getSiteOrigin());

        return $this;
    }

    /**
     * Adds the given certificates to the list of trusted intermediate Certificate Authorities
     * used during validation of subject and OCSP responder certificates.
     * In order for a user or OCSP responder certificate to be considered valid, the certificate
     * of the issuer of the certificate must be present in this list.\
     * At least one trusted intermediate Certificate Authority must be provided as a mandatory configuration parameter.
     *
     * @param X509[] $certificates trusted intermediate Certificate Authority certificates
     *
     * @throws InvalidArgumentException
     * @throws Throwable
     *
     * @return AuthTokenValidatorBuilder the builder instance for method chaining
     */
    public function withTrustedCertificateAuthorities(X509 ...$certificates): AuthTokenValidatorBuilder
    {
        array_push($this->configuration->getTrustedCertificates(), ...$certificates);

        $this->logger->debug(
            'Trusted intermediate certificate authorities added: '.json_encode(X509Array::getSubjectDNs(null, ...$this->configuration->getTrustedCertificates()))
        );

        return $this;
    }

    /**
     * Adds the given policies to the list of disallowed user certificate policies.
     * In order for the user certificate to be considered valid, it must not contain any policies
     * present in this list.
     *
     * @param string ...$policies disallowed user certificate policies
     *
     * @return AuthTokenValidatorBuilder the builder instance for method chaining
     */
    public function withDisallowedCertificatePolicyIds(string ...$policies): AuthTokenValidatorBuilder
    {
        array_push($this->configuration->getDisallowedSubjectCertificatePolicyIds(), ...$policies);
        $this->logger->debug('Disallowed subject certificate policies set to '.json_encode($this->configuration->getDisallowedSubjectCertificatePolicyIds()));

        return $this;
    }

    /**
     * Turns off user certificate revocation check with OCSP.
     *
     * Turning off user certificate revocation check with OCSP is dangerous and should be
     * used only in exceptional circumstances.
     * By default, the revocation check is turned on.
     *
     * @return AuthTokenValidatorBuilder the builder instance for method chaining
     */
    public function withoutUserCertificateRevocationCheckWithOcsp(): AuthTokenValidatorBuilder
    {
        $this->configuration->setUserCertificateRevocationCheckWithOcspDisabled();
        $this->logger->warning('User certificate revocation check with OCSP is disabled, '.'you should turn off the revocation check only in exceptional circumstances');

        return $this;
    }

    /**
     * Sets both the connection and response timeout of user certificate revocation check OCSP requests.
     *
     * This is an optional configuration parameter, the default is 5 seconds.
     *
     * @param int $ocspRequestTimeout the duration of OCSP request connection and response timeout
     *
     * @return AuthTokenValidatorBuilder the builder instance for method chaining
     */
    public function withOcspRequestTimeout(int $ocspRequestTimeoutSeconds): AuthTokenValidatorBuilder
    {
        $this->configuration->setOcspRequestTimeoutSeconds($ocspRequestTimeoutSeconds);
        $this->logger->debug('OCSP request timeout set to '.$ocspRequestTimeoutSeconds.' seconds.');

        return $this;
    }

    /**
     * Adds the given URLs to the list of OCSP URLs for which the nonce protocol extension will be disabled.
     * The OCSP URL is extracted from the user certificate and some OCSP services don't support the nonce extension.
     *
     * @param URI ...$uris urls OCSP URLs for which the nonce protocol extension will be disabled
     *
     * @return AuthTokenValidatorBuilder the builder instance for method chaining
     */
    public function withNonceDisabledOcspUrls(URI ...$uris): AuthTokenValidatorBuilder
    {
        foreach ($uris as $uri) {
            $this->configuration->getNonceDisabledOcspUrls()->pushItem($uri);
        }
        $this->logger->debug('OCSP URLs for which the nonce protocol extension is disabled set to '.implode(', ', $this->configuration->getNonceDisabledOcspUrls()->getUrls()));

        return $this;
    }

    /**
     * Activates the provided designated OCSP service for user certificate revocation check with OCSP.
     * The designated service is only used for checking the status of the certificates whose issuers are
     * supported by the service, falling back to the default OCSP service access location from
     * the certificate's AIA extension if not.
     *
     * @param DesignatedOcspServiceConfiguration serviceConfiguration configuration of the designated OCSP service
     *
     * @return AuthTokenValidatorBuilder the builder instance for method chaining
     */
    public function withDesignatedOcspServiceConfiguration(DesignatedOcspServiceConfiguration $serviceConfiguration): AuthTokenValidatorBuilder
    {
        $this->configuration->setDesignatedOcspServiceConfiguration($serviceConfiguration);
        $this->logger->debug('Using designated OCSP service configuration');

        return $this;
    }

	/**
	 * Validates the configuration and builds the AuthTokenValidator object with it.
     * The returned AuthTokenValidator object is immutable/thread-safe.
	 * @return AuthTokenValidator
	 * @throws GlobalInvalidArgumentException
	 */
    public function build(): AuthTokenValidator
    {
        $this->configuration->validate();

        return new AuthTokenValidatorImpl($this->configuration);
    }
}
