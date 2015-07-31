<?php

namespace wmc\helpers;

class IPHelper
{
    /**
     * This function converts a human readable IPv4 or IPv6 address into an address family appropriate
     * 32bit or 128bit binary structure. Suitable for MySQL VARBINARY(16) columns.
     * @param $ip A human readable IPv4 or IPv6 address.
     * @return null|string Returns the in_addr representation of the given ip or NULL if ip is invalid
     */

    public static function toBinaryIp($ip) {
        $inetPton = inet_pton($ip);
        return $inetPton === false ? null : $inetPton;
    }

}