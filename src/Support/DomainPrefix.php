<?php

namespace MPYazilim\Elastic\Support;

final class DomainPrefix
{
    public static function make(?string $domain = null): string
    {
        $domain ??= $_SERVER['HTTP_HOST'] ?? 'localhost';
        $domain = preg_replace('/^www\./i', '', $domain);
        $host = parse_url($domain, PHP_URL_HOST) ?: $domain;

        $normalized = strtolower(
            preg_replace('/[^a-z0-9]/i', '', $host)
        );

        return substr(md5($normalized), 0, 6);
    }
}
