<?php

declare(strict_types=1);

namespace muzosh\web_eid_authtoken_validation_php\validator;

use GuzzleHttp\Psr7\Uri;
use Monolog\Logger;
use muzosh\web_eid_authtoken_validation_php\util\WebEidLogger;
use muzosh\web_eid_authtoken_validation_php\util\X509Array;
use muzosh\web_eid_authtoken_validation_php\validator\ocsp\service\DesignatedOcspServiceConfiguration;
use phpseclib3\File\X509;

class AuthTokenValidatorBuilder
{
    private Logger $logger;
    private AuthTokenValidationConfiguration $configuration;

    public function __construct()
    {
        $this->logger = WebEidLogger::getLogger(AuthTokenValidatorBuilder::class);
        $this->configuration = new AuthTokenValidationConfiguration();
    }

    /**
     * Sets the expected site origin, i.e. the domain that the application is running on.
     * <p>
     * Origin is a mandatory configuration parameter.
     *
     * @param origin origin URL as defined in <a href="https://developer.mozilla.org/en-US/docs/Web/API/Location/origin">MDN</a>,
     *               in the form of {@code <scheme> "://" <hostname> [ ":" <port> ]}
     *
     * @return the builder instance for method chaining
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
     * of the issuer of the certificate must be present in this list.
     * <p>
     * At least one trusted intermediate Certificate Authority must be provided as a mandatory configuration parameter.
     *
     * @param certificates trusted intermediate Certificate Authority certificates
     *
     * @return the builder instance for method chaining
     */
    public function withTrustedCertificateAuthorities(X509 ...$certificates): AuthTokenValidatorBuilder
    {
        array_push($this->configuration->getTrustedCACertificates(), ...$certificates);

        $this->logger->debug(
            'Trusted intermediate certificate authorities set to '.json_encode(X509Array::getSubjectDNs(null, ...$this->configuration->getTrustedCACertificates()))
        );

        return $this;
    }

    /**
     * Adds the given policies to the list of disallowed user certificate policies.
     * In order for the user certificate to be considered valid, it must not contain any policies
     * present in this list.
     *
     * @param policies disallowed user certificate policies
     *
     * @return the builder instance for method chaining
     */
    public function withDisallowedCertificatePolicyIds(string ...$policies): AuthTokenValidatorBuilder
    {
        array_push($this->configuration->getDisallowedSubjectCertificatePolicyIds(), ...$policies);
        $this->logger->debug('Disallowed subject certificate policies set to '.json_encode($this->configuration->getDisallowedSubjectCertificatePolicyIds()));

        return $this;
    }

    /**
     * Turns off user certificate revocation check with OCSP.
     * <p>
     * <b>Turning off user certificate revocation check with OCSP is dangerous and should be
     * used only in exceptional circumstances.</b>
     * By default, the revocation check is turned on.
     *
     * @return the builder instance for method chaining
     */
    public function withoutUserCertificateRevocationCheckWithOcsp()
    {
        $this->configuration->setUserCertificateRevocationCheckWithOcspDisabled();
        $this->logger->warning('User certificate revocation check with OCSP is disabled, ' +
            'you should turn off the revocation check only in exceptional circumstances');

        return $this;
    }

    /**
     * Sets both the connection and response timeout of user certificate revocation check OCSP requests.
     * <p>
     * This is an optional configuration parameter, the default is 5 seconds.
     *
     * @param ocspRequestTimeout the duration of OCSP request connection and response timeout
     *
     * @return the builder instance for method chaining
     */
    public function withOcspRequestTimeout(int $ocspRequestTimeoutSeconds): AuthTokenValidatorBuilder
    {
        $this->configuration->setOcspRequestTimeout($ocspRequestTimeoutSeconds);
        $this->logger->debug('OCSP request timeout set to '.$ocspRequestTimeoutSeconds.' seconds.');

        return $this;
    }

    /**
     * Adds the given URLs to the list of OCSP URLs for which the nonce protocol extension will be disabled.
     * The OCSP URL is extracted from the user certificate and some OCSP services don't support the nonce extension.
     *
     * @param urls OCSP URLs for which the nonce protocol extension will be disabled
     *
     * @return the builder instance for method chaining
     */
    public function withNonceDisabledOcspUrls(URI ...$urls): AuthTokenValidatorBuilder
    {
        array_push($this->configuration->getNonceDisabledOcspUrls(), ...array_unique($urls, SORT_REGULAR));
        $this->logger->debug('OCSP URLs for which the nonce protocol extension is disabled set to '.json_encode($this->configuration->getNonceDisabledOcspUrls()));

        return $this;
    }

    /**
     * Activates the provided designated OCSP service for user certificate revocation check with OCSP.
     * The designated service is only used for checking the status of the certificates whose issuers are
     * supported by the service, falling back to the default OCSP service access location from
     * the certificate's AIA extension if not.
     *
     * @param serviceConfiguration configuration of the designated OCSP service
     *
     * @return the builder instance for method chaining
     */
    public function withDesignatedOcspServiceConfiguration(DesignatedOcspServiceConfiguration $serviceConfiguration): AuthTokenValidatorBuilder
    {
        $this->configuration->setDesignatedOcspServiceConfiguration($serviceConfiguration);
        $this->logger->debug('Using designated OCSP service configuration');

        return $this;
    }

    /**
     * Validates the configuration and builds the {@link AuthTokenValidator} object with it.
     * The returned {@link AuthTokenValidator} object is immutable/thread-safe.
     *
     * @throws NullPointerException     when required parameters are null
     * @throws IllegalArgumentException when any parameter is invalid
     * @throws RuntimeException         when JCE configuration is invalid
     *
     * @return the configured authentication token validator object
     */
    public function build(): AuthTokenValidator
    {
        $this->configuration->validate();

        return new AuthTokenValidatorImpl($this->configuration);
    }
}