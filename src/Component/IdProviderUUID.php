<?php
namespace Pluf\Workflow\Component;

/**
 * Generates ID based on UUID V4
 * @author maso
 *
 */
class IdProviderUUID implements IdProvider
{

    /**
     * 
     * {@inheritDoc}
     * @see \Pluf\Workflow\Component\IdProvider::get()
     */
    public function get(): string
    {
        if (function_exists('com_create_guid') === true) {
            return trim(com_create_guid(), '{}');
        }

        if (function_exists('openssl_random_pseudo_bytes')) {
            $data = openssl_random_pseudo_bytes(16);
        } else {
            $data = $data ?? random_bytes(16);
        }

        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0100
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

