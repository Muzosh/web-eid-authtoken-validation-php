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

namespace muzosh\web_eid_authtoken_validation_php\testutil;

use muzosh\web_eid_authtoken_validation_php\certificate\CertificateLoader;
use phpseclib3\File\X509;

class Certificates
{
    private const JAAK_KRISTJAN_ESTEID2018_CERT = 'MIIEAzCCA2WgAwIBAgIQOWkBWXNDJm1byFd3XsWkvjAKBggqhkjOPQQDBDBgMQswCQYDVQQGEwJFRTEbMBkGA1UECgwSU0sgSUQgU29sdXRpb25zIEFTMRcwFQYDVQRhDA5OVFJFRS0xMDc0NzAxMzEbMBkGA1UEAwwSVEVTVCBvZiBFU1RFSUQyMDE4MB4XDTE4MTAxODA5NTA0N1oXDTIzMTAxNzIxNTk1OVowfzELMAkGA1UEBhMCRUUxKjAoBgNVBAMMIUrDlUVPUkcsSkFBSy1LUklTVEpBTiwzODAwMTA4NTcxODEQMA4GA1UEBAwHSsOVRU9SRzEWMBQGA1UEKgwNSkFBSy1LUklTVEpBTjEaMBgGA1UEBRMRUE5PRUUtMzgwMDEwODU3MTgwdjAQBgcqhkjOPQIBBgUrgQQAIgNiAAR5k1lXzvSeI9O/1s1pZvjhEW8nItJoG0EBFxmLEY6S7ki1vF2Q3TEDx6dNztI1Xtx96cs8r4zYTwdiQoDg7k3diUuR9nTWGxQEMO1FDo4Y9fAmiPGWT++GuOVoZQY3XxijggHDMIIBvzAJBgNVHRMEAjAAMA4GA1UdDwEB/wQEAwIDiDBHBgNVHSAEQDA+MDIGCysGAQQBg5EhAQIBMCMwIQYIKwYBBQUHAgEWFWh0dHBzOi8vd3d3LnNrLmVlL0NQUzAIBgYEAI96AQIwHwYDVR0RBBgwFoEUMzgwMDEwODU3MThAZWVzdGkuZWUwHQYDVR0OBBYEFOQsvTQJEBVMMSmhyZX5bibYJubAMGEGCCsGAQUFBwEDBFUwUzBRBgYEAI5GAQUwRzBFFj9odHRwczovL3NrLmVlL2VuL3JlcG9zaXRvcnkvY29uZGl0aW9ucy1mb3ItdXNlLW9mLWNlcnRpZmljYXRlcy8TAkVOMCAGA1UdJQEB/wQWMBQGCCsGAQUFBwMCBggrBgEFBQcDBDAfBgNVHSMEGDAWgBTAhJkpxE6fOwI09pnhClYACCk+ezBzBggrBgEFBQcBAQRnMGUwLAYIKwYBBQUHMAGGIGh0dHA6Ly9haWEuZGVtby5zay5lZS9lc3RlaWQyMDE4MDUGCCsGAQUFBzAChilodHRwOi8vYy5zay5lZS9UZXN0X29mX0VTVEVJRDIwMTguZGVyLmNydDAKBggqhkjOPQQDBAOBiwAwgYcCQgH1UsmMdtLZti51Fq2QR4wUkAwpsnhsBV2HQqUXFYBJ7EXnLCkaXjdZKkHpABfM0QEx7UUhaI4i53jiJ7E1Y7WOAAJBDX4z61pniHJapI1bkMIiJQ/ti7ha8fdJSMSpAds5CyHIyHkQzWlVy86f9mA7Eu3oRO/1q+eFUzDbNN3Vvy7gQWQ=';
    private const MARILIIS_ESTEID2015_CERT = 'MIIFwjCCA6qgAwIBAgIQY+LgQ6n0BURZ048wIEiYHjANBgkqhkiG9w0BAQsFADBrMQswCQYDVQQGEwJFRTEiMCAGA1UECgwZQVMgU2VydGlmaXRzZWVyaW1pc2tlc2t1czEXMBUGA1UEYQwOTlRSRUUtMTA3NDcwMTMxHzAdBgNVBAMMFlRFU1Qgb2YgRVNURUlELVNLIDIwMTUwHhcNMTcxMDAzMTMyMjU2WhcNMjIxMDAyMjA1OTU5WjCBnjELMAkGA1UEBhMCRUUxDzANBgNVBAoMBkVTVEVJRDEaMBgGA1UECwwRZGlnaXRhbCBzaWduYXR1cmUxJjAkBgNVBAMMHU3DhE5OSUssTUFSSS1MSUlTLDYxNzEwMDMwMTYzMRAwDgYDVQQEDAdNw4ROTklLMRIwEAYDVQQqDAlNQVJJLUxJSVMxFDASBgNVBAUTCzYxNzEwMDMwMTYzMHYwEAYHKoZIzj0CAQYFK4EEACIDYgAE+nNdtmZ2Ve3XXtjBEGwpvVrDIg7slPfLlyHbCBFMXevfqW5KsXIOy6E2A+Yof+/cqRlY4IhsX2Ka9SsJSo8/EekasFasLFPw9ZBE3MG0nn5zaatg45VSjnPinMmrzFzxo4IB2jCCAdYwCQYDVR0TBAIwADAOBgNVHQ8BAf8EBAMCBkAwgYsGA1UdIASBgzCBgDBzBgkrBgEEAc4fAwEwZjAvBggrBgEFBQcCARYjaHR0cHM6Ly93d3cuc2suZWUvcmVwb3NpdG9vcml1bS9DUFMwMwYIKwYBBQUHAgIwJwwlQWludWx0IHRlc3RpbWlzZWtzLiBPbmx5IGZvciB0ZXN0aW5nLjAJBgcEAIvsQAECMB0GA1UdDgQWBBTiw6M0uow+u6sfhgJAWCSvtkB/ejAiBggrBgEFBQcBAwQWMBQwCAYGBACORgEBMAgGBgQAjkYBBDAfBgNVHSMEGDAWgBRJwPJEOWXVm0Y7DThgg7HWLSiGpjCBgwYIKwYBBQUHAQEEdzB1MCwGCCsGAQUFBzABhiBodHRwOi8vYWlhLmRlbW8uc2suZWUvZXN0ZWlkMjAxNTBFBggrBgEFBQcwAoY5aHR0cHM6Ly9zay5lZS91cGxvYWQvZmlsZXMvVEVTVF9vZl9FU1RFSUQtU0tfMjAxNS5kZXIuY3J0MEEGA1UdHwQ6MDgwNqA0oDKGMGh0dHA6Ly93d3cuc2suZWUvY3Jscy9lc3RlaWQvdGVzdF9lc3RlaWQyMDE1LmNybDANBgkqhkiG9w0BAQsFAAOCAgEAEWBdwmzo/yRncJXKvrE+A1G6yQaBNarKectI5uk18BewYEA4QkhmIwOCwD83jBDB9JF+kuODMHsnvz2mfhwaB/uJIPwfBDQ5JCMBdHPsxLN9nzW/UUzqv2UDMwFkibHCcfV5lTBcmOd7FagUHTUm+8gRlWbDiVl5yPochdJgGYPV+fs/jc5ttHaBvBon0z9LbI4qi0VXdRmV0iogErh8JF5yfGkbfGRaMkWkNYQtQ68i/hPe6MaUxL2/MMt4YTyXtVghmc3ZKZIyp4j0+jlK4vL+d4gaE+TvoQvh6HrmP145FqlMDurATWdB069+hdDLO5fI6AYkc79D5XPKwQ/f1MBufLtBYtOJmtpLT+tdBt/EqOEIO/0FeHcXZlFioNMuxBBeTE/QcDtJ2jxTcg8jNOoepS0wjuxBon9iI1710SR53DLGSWdL52lPoBFacnyPQI1htXVUkJ8icMQKYe3BLt1Ha2cvsA4n4IpjqVROX4mzoPL1hg/aJlD+W2uI2ppYRUNY5FX7C0R+AYzMpOahQ7STQfUxtEnKW98e1I33LWwpjJW9q4htsZeXs4Zatf9ssfUW0VA49tnI28kkN2D8aw1NgWfzVlnJKkEj0qa3ewLZK577j8MexAetT/7leH6mqewr9ewC/tKbYjhufieXx6RPcRC4OZsxtii7ih8TqRg=';

    private static ?X509 $testEsteid2018CA = null;
    private static ?X509 $testEsteid2015CA = null;
    private static ?X509 $testSkOcspResponder2020 = null;
    private static ?X509 $testEsteid2018CAGov = null;

    private static ?X509 $jaakKristjanEsteid2018Cert = null;
    private static ?X509 $mariliisEsteid2015Cert = null;

    public static function loadCertificates(): void
    {
        $certificates = CertificateLoader::loadCertificatesFromPath(__DIR__.'/../_resources', 'TEST_of_ESTEID-SK_2015.cer', 'TEST_of_ESTEID2018.cer', 'TEST_of_SK_OCSP_RESPONDER_2020.cer', 'TEST_of_EE-GovCA2018.crt');
        self::$testEsteid2015CA = $certificates[0];
        self::$testEsteid2018CA = $certificates[1];
        self::$testSkOcspResponder2020 = $certificates[2];
        self::$testEsteid2018CAGov = $certificates[3];
    }

    public static function getTestEsteid2018CA(): X509
    {
        if (null == self::$testEsteid2018CA) {
            self::loadCertificates();
        }

        return self::$testEsteid2018CA;
    }

    public static function getTestEsteid2018CAGov(): X509
    {
        if (null == self::$testEsteid2018CAGov) {
            self::loadCertificates();
        }

        return self::$testEsteid2018CAGov;
    }

    public static function getTestEsteid2015CA(): X509
    {
        if (null == self::$testEsteid2015CA) {
            self::loadCertificates();
        }

        return self::$testEsteid2015CA;
    }

    public static function getTestSkOcspResponder2020(): ?X509
    {
        if (null == self::$testSkOcspResponder2020) {
            self::loadCertificates();
        }

        return self::$testSkOcspResponder2020;
    }

    public static function getJaakKristjanEsteid2018Cert(): X509
    {
        if (null == self::$jaakKristjanEsteid2018Cert) {
            self::$jaakKristjanEsteid2018Cert = new X509();
            self::$jaakKristjanEsteid2018Cert->loadX509(self::JAAK_KRISTJAN_ESTEID2018_CERT);
        }

        return self::$jaakKristjanEsteid2018Cert;
    }

    public static function getMariliisEsteid2015Cert(): X509
    {
        if (null == self::$mariliisEsteid2015Cert) {
            self::$mariliisEsteid2015Cert = new X509();
            self::$mariliisEsteid2015Cert->loadX509(self::MARILIIS_ESTEID2015_CERT);
        }

        return self::$mariliisEsteid2015Cert;
    }
}
