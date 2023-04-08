<?php

namespace Niwee\Niwhiboutik;

class Utils
{
    public static function url_exists(
        string $url,
               $context
    ): bool
    {
        $headers = get_headers(
            $url,
            false,
            $context
        );

        // Check if there is a php_network_getaddresses error
        if (strpos($headers[0], 'php_network_getaddresses') !== false) {
            return false;
        }

        return (bool)stripos(
            $headers[0],
            "200 OK"
        );
    }

    /**
     * @param $time
     * @return string
     */
    public static function formatMicrotime($time): string
    {
        return date(
            'i:s',
            (int)$time
        );
    }
}