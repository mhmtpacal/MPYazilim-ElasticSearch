<?php

namespace MPYazilim\Elastic\Account;

use InvalidArgumentException;
use MPYazilim\Elastic\Support\DomainPrefix;

final class ElasticAccount
{
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private string $domain;
    private string $prefix;

    private const REQUIRED = [
        'host',
        'port',
        'username',
        'password',
        'domain',
    ];

    public function __construct(array $params)
    {
        foreach (self::REQUIRED as $key) {
            if (!isset($params[$key]) || trim((string)$params[$key]) === '') {
                throw new InvalidArgumentException(
                    "Missing required account parameter: {$key}"
                );
            }
        }

        $this->host     = trim($params['host']);
        $this->port     = (int)$params['port'];
        $this->username = trim($params['username']);
        $this->password = trim($params['password']);
        $this->domain   = trim($params['domain']);

        $this->prefix = DomainPrefix::make($this->domain) . '_';
    }

    public function host(): string { return $this->host; }
    public function port(): int { return $this->port; }
    public function username(): string { return $this->username; }
    public function password(): string { return $this->password; }
    public function domain(): string { return $this->domain; }
    public function prefix(): string { return $this->prefix; }
}
