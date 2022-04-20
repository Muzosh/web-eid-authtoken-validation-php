<?php

declare(strict_types=1);

namespace muzosh\web_eid_authtoken_validation_php\validator\ocsp\service;

use muzosh\web_eid_authtoken_validation_php\util\CertStore;
use muzosh\web_eid_authtoken_validation_php\util\TrustedAnchors;

class AiaOcspServiceConfiguration
{
    private $nonceDisabledOcspUrls;
    private $trustedCACertificateAnchors;
    private $trustedCACertificateCertStore;

    // CertStore + TrustedAnchors in Java vs TrustedCertificates in C#
    public function __construct(array $nonceDisabledOcspUrls, TrustedAnchors $trustedCACertificateAnchors)// , CertStore $trustedCACertificateCertStore)
    {
        $this->nonceDisabledOcspUrls = $nonceDisabledOcspUrls;
        $this->trustedCACertificateAnchors = $trustedCACertificateAnchors;
        // CertStore + TrustedAnchors in Java vs TrustedCertificates in C#
        // $this->trustedCACertificateCertStore = $trustedCACertificateCertStore;
    }

    public function getNonceDisabledOcspUrls(): array
    {
        return $this->nonceDisabledOcspUrls;
    }

    public function getTrustedCACertificateAnchors(): TrustedAnchors
    {
        return $this->trustedCACertificateAnchors;
    }

    // CertStore + TrustedAnchors in Java vs TrustedCertificates in C#
    // public function getTrustedCACertificateCertStore(): CertStore
    // {
    //     return $this->trustedCACertificateCertStore;
    // }
}