<?php

declare(strict_types=1);

namespace muzosh\web_eid_authtoken_validation_php\util;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use muzosh\web_eid_authtoken_validation_php\validator\certvalidators\SubjectCertificateValidator;
use phpseclib3\File\X509;

// TODO: can be changed for define(currentClass::class . "CONST_NAME", EXPRESSION)
// for nonmalluable variable
final class TrustedAnchors
{
    private $certificates;

    public function __construct(array $certificates)
    {
        $this->certificates = new X509Array(...$certificates);
    }

    public function getTrustedAnchors()
    {
        return $this->certificates;
    }
}

// TODO: can be changed for define(currentClass::class . "CONST_NAME", EXPRESSION)
// for nonmalluable variable
final class CertStore
{
    private $certificates;

    public function __construct(array $certificates)
    {
        $this->certificates = new X509UniqueArray(...$certificates);
    }

    public function getCertificates()
    {
        return $this->certificates;
    }
}

/* TODO: what is the best way of ensuring typed array?
    // https://www.cloudsavvyit.com/10040/approaches-to-creating-typed-arrays-in-php/
    A) ignore and use arrays
    B) Variadic Arguments - only once per function
    C) Collection classes <==

    in case i need more array functions https://gist.github.com/MightyPork/5ad28f208f046a24831c
    */
abstract class CustomObjectArray implements ArrayAccess, IteratorAggregate
{
    protected array $array;

    public function offsetExists($offset): bool
    {
        return isset($this->array[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->array[$offset];
    }

    public function pushItem($value): void
    {
        $this->checkInstance($value);
        array_push($this->array, $value);
    }

    public function offsetUnset($offset): void
    {
        unset($this->array[$offset]);
    }

    public function offsetSet($offset, $value): void
    {
        $this->checkInstance($value);
        $this->array[$offset] = $value;
    }

    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->array);
    }

    abstract public function checkInstance($value): void;

    protected function makeUnique(): void
    {
        $this->array = array_unique($this->array, SORT_REGULAR);
    }
}

final class SubjectCertificateValidatorArray extends CustomObjectArray
{
    public function __construct(SubjectCertificateValidator ...$array)
    {
        $this->array = $array;
    }

    public function checkInstance($value): void
    {
        if (!$value instanceof SubjectCertificateValidator) {
            throw new \TypeError('Can only insert '.SubjectCertificateValidator::class);
        }
    }
}

class X509Array extends CustomObjectArray
{
    public function __construct(X509 ...$array)
    {
        $this->array = $array;
    }

    public function checkInstance($value): void
    {
        if (!$value instanceof X509) {
            throw new \TypeError('Can only insert '.X509::class);
        }
    }

	public function getSubjectDNs(): array{
		return array_map(function($item){
			return $item->getSubjectDN(X509::DN_STRING);
		}, $this->array);
	}
}

final class X509UniqueArray extends X509Array
{
    public function __construct(X509 ...$certificates)
    {
        parent::__construct(...$certificates);
        $this->makeUnique();
    }

    public function offsetSet($offset, $value): void
    {
        $this->checkInstance($value);

        if (in_array($value, $this->array, true)) {
            throw new \InvalidArgumentException('This object already is in the array.');
        }

        $this->array[$offset] = $value;
    }
}

// TODO: remove this after testing
$certificate = '-----BEGIN CERTIFICATE-----
MIIEBDCCA2WgAwIBAgIQH9NeN14jo0ReaircrN2YvDAKBggqhkjOPQQDBDBgMQswCQYDVQQGEwJFRTEbMBkGA1UECgwSU0sgSUQgU29sdXRpb25zIEFTMRcwFQYDVQRhDA5OVFJFRS0xMDc0NzAxMzEbMBkGA1UEAwwSVEVTVCBvZiBFU1RFSUQyMDE4MB4XDTIwMDMxMjEyMjgxMloXDTI1MDMxMjIxNTk1OVowfzELMAkGA1UEBhMCRUUxKjAoBgNVBAMMIUrDlUVPUkcsSkFBSy1LUklTVEpBTiwzODAwMTA4NTcxODEQMA4GA1UEBAwHSsOVRU9SRzEWMBQGA1UEKgwNSkFBSy1LUklTVEpBTjEaMBgGA1UEBRMRUE5PRUUtMzgwMDEwODU3MTgwdjAQBgcqhkjOPQIBBgUrgQQAIgNiAARVeP+9l3b1mm3fMHPeCFLbD7esXI8lDc+soWCBoMnZGo3d2Rg/mzKCIWJtw+JhcN7RwFFH9cwZ8Gni4C3QFYBIIJ2GdjX2KQfEkDvRsnKw6ZZmJQ+HC4ZFew3r8gauhfejggHDMIIBvzAJBgNVHRMEAjAAMA4GA1UdDwEB/wQEAwIDiDBHBgNVHSAEQDA+MDIGCysGAQQBg5EhAQIBMCMwIQYIKwYBBQUHAgEWFWh0dHBzOi8vd3d3LnNrLmVlL0NQUzAIBgYEAI96AQIwHwYDVR0RBBgwFoEUMzgwMDEwODU3MThAZWVzdGkuZWUwHQYDVR0OBBYEFOfk7lPOq6rb9IbFZF1q97kJ4s2iMGEGCCsGAQUFBwEDBFUwUzBRBgYEAI5GAQUwRzBFFj9odHRwczovL3NrLmVlL2VuL3JlcG9zaXRvcnkvY29uZGl0aW9ucy1mb3ItdXNlLW9mLWNlcnRpZmljYXRlcy8TAkVOMCAGA1UdJQEB/wQWMBQGCCsGAQUFBwMCBggrBgEFBQcDBDAfBgNVHSMEGDAWgBTAhJkpxE6fOwI09pnhClYACCk+ezBzBggrBgEFBQcBAQRnMGUwLAYIKwYBBQUHMAGGIGh0dHA6Ly9haWEuZGVtby5zay5lZS9lc3RlaWQyMDE4MDUGCCsGAQUFBzAChilodHRwOi8vYy5zay5lZS9UZXN0X29mX0VTVEVJRDIwMTguZGVyLmNydDAKBggqhkjOPQQDBAOBjAAwgYgCQgEQRbzFOSHIcmIEKczhN8xuteYgN2zEXZSJdP0q1iH1RR2AzZ8Ddz6SKRn/bZSzjcd4b7h3AyOEQr2hcidYkxT7sAJCAMPtOUryqp2WbTEUoOpbWrKqp8GjaAiVpBGDn/Xdu5M2Z6dvwZHnFGgRrZXtyUbcAgRW7MQJ0s/9GCVro3iqUzNN
-----END CERTIFICATE-----
';

require __DIR__.'/../../vendor/autoload.php';

$x509 = new X509();
$seclib = $x509->loadX509($certificate);

$certArray = [$x509, clone $x509, clone $x509];

$ta = new X509Array(...$certArray);
$taq = new X509UniqueArray(...$certArray);

$ta->pushItem(clone $x509);

// $openssl = \openssl_x509_parse($certificate);

// title case
// ucwords(strtolower($openssl['subject']['GN']), '\-');

$ar = $ta->getSubjectDNs();

$test = 10;
