<?php

namespace MPYazilim\Elastic\Client;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use MPYazilim\Elastic\Account\ElasticAccount;

final class ElasticClient
{
    public Client $client;
    public string $prefix;

    public function __construct(ElasticAccount $account)
    {
        $this->client = ClientBuilder::create()
            ->setHosts([$account->host() . ':' . $account->port()])
            ->setBasicAuthentication(
                $account->username(),
                $account->password()
            )
            ->setSSLVerification(false)
            ->setElasticMetaHeader(false)
            ->build();

        $this->prefix = $account->prefix();
    }
}
