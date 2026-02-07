<?php

namespace MPYazilim\Elastic\Resources;

use MPYazilim\Elastic\Client\ElasticClient;

abstract class AbstractResource
{
    protected ElasticClient $es;
    protected string $base;

    public function __construct(ElasticClient $es)
    {
        $this->es = $es;
    }

    protected function alias(): string
    {
        return $this->es->prefix . $this->base;
    }

    protected function versionedIndex(): string
    {
        return $this->alias() . '_v' . date('Ymd_His');
    }

    protected function getCurrentAliasIndex(): ?string
    {
        try {
            $resp = $this->es->client->indices()->getAlias([
                'name' => $this->alias()
            ])->asArray();

            return array_key_first($resp);
        } catch (\Throwable) {
            return null;
        }
    }

    protected function deleteIndex(string $index): void
    {
        try {
            $this->es->client->indices()->delete([
                'index' => $index
            ]);
        } catch (\Throwable) {
        }
    }

    abstract protected function mapping(): array;
}
