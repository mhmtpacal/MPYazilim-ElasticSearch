<?php

namespace MPYazilim\Elastic;

use MPYazilim\Elastic\Account\ElasticAccount;
use MPYazilim\Elastic\Client\ElasticClient;
use MPYazilim\Elastic\Resources\Urunler;
use MPYazilim\Elastic\Resources\Kategoriler;

final class MPElastic
{
    private ElasticClient $client;

    private function __construct(ElasticClient $client)
    {
        $this->client = $client;
    }

    /**
     * @param array $params ['host','port','username','password','domain']
     */
    public static function account(array $params): self
    {
        return new self(
            new ElasticClient(new ElasticAccount($params))
        );
    }

    public function urunler(): Urunler
    {
        return new Urunler($this->client);
    }

    public function kategoriler(): Kategoriler
    {
        return new Kategoriler($this->client);
    }
}
