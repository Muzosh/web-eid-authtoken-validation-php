<?php

declare(strict_types=1);

namespace muzosh\web_eid_authtoken_validation_php\validator\ocsp;
class OcspServiceProvider {

    private final DesignatedOcspService designatedOcspService;
    private final AiaOcspServiceConfiguration aiaOcspServiceConfiguration;

    public OcspServiceProvider(DesignatedOcspServiceConfiguration designatedOcspServiceConfiguration, AiaOcspServiceConfiguration aiaOcspServiceConfiguration) {
        designatedOcspService = designatedOcspServiceConfiguration != null ?
            new DesignatedOcspService(designatedOcspServiceConfiguration)
            : null;
        this.aiaOcspServiceConfiguration = Objects.requireNonNull(aiaOcspServiceConfiguration, "aiaOcspServiceConfiguration");
    }

    /**
     * A static factory method that returns either the designated or AIA OCSP service instance depending on whether
     * the designated OCSP service is configured and supports the issuer of the certificate.
     *
     * @param certificate subject certificate that is to be checked with OCSP
     * @return either the designated or AIA OCSP service instance
     * @throws AuthTokenException when AIA URL is not found in certificate
     * @throws CertificateEncodingException when certificate is invalid
     */
    public OcspService getService(X509Certificate certificate) throws AuthTokenException, CertificateEncodingException {
        if (designatedOcspService != null && designatedOcspService.supportsIssuerOf(certificate)) {
            return designatedOcspService;
        }
        return new AiaOcspService(aiaOcspServiceConfiguration, certificate);
    }

}
