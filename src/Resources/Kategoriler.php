<?php

namespace MPYazilim\Elastic\Resources;

final class Kategoriler extends AbstractResource
{
    protected string $base = 'kategoriler';

    protected function mapping(): array
    {
        return [
            'settings' => [
                'analysis' => [
                    'analyzer' => [
                        'tr_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => ['lowercase', 'asciifolding'],
                        ],
                    ],
                ],
                'index' => [
                    'number_of_shards' => 1,
                    'number_of_replicas' => 0,
                ],
            ],
            'mappings' => [
                'dynamic' => false,
                'properties' => [
                    'kategoriId' => ['type' => 'integer'],
                    'name' => ['type' => 'text', 'analyzer' => 'tr_analyzer'],
                    'name_suggest' => ['type' => 'search_as_you_type'],
                    'url' => ['type' => 'keyword'],
                ],
            ],
        ];
    }

    public function rebuild(array $items): array
    {
        $oldIndex = $this->getCurrentAliasIndex();
        $newIndex = $this->versionedIndex();

        $this->es->client->indices()->create([
            'index' => $newIndex,
            'body'  => $this->mapping()
        ]);

        $body = [];
        foreach ($items as $item) {
            if (!isset($item['kategoriId'])) continue;

            $body[] = [
                'index' => [
                    '_index' => $newIndex,
                    '_id' => (string)$item['kategoriId']
                ]
            ];
            $body[] = $item;
        }

        if ($body) {
            $this->es->client->bulk(['body' => $body]);
        }

        $this->es->client->indices()->updateAliases([
            'body' => [
                'actions' => [
                    ['remove' => ['index' => '*', 'alias' => $this->alias()]],
                    ['add' => ['index' => $newIndex, 'alias' => $this->alias()]],
                ]
            ]
        ]);

        if ($oldIndex && $oldIndex !== $newIndex) {
            $this->deleteIndex($oldIndex);
        }

        return ['ok' => true, 'index' => $newIndex];
    }

    public function search(string $q, int $limit = 50): array
    {
        $resp = $this->es->client->search([
            'index' => $this->alias(),
            'size' => $limit,
            'body' => [
                'query' => [
                    'dis_max' => [
                        'queries' => [
                            [
                                'multi_match' => [
                                    'query' => $q,
                                    'type' => 'bool_prefix',
                                    'fields' => [
                                        'name_suggest^6',
                                        'name_suggest._2gram^4',
                                        'name_suggest._3gram^3',
                                    ],
                                ],
                            ],
                            [
                                'match' => [
                                    'name' => [
                                        'query' => $q,
                                        'operator' => 'and',
                                        'boost' => 4,
                                    ],
                                ],
                            ],
                            [
                                'match' => [
                                    'name' => [
                                        'query' => $q,
                                        'fuzziness' => 'AUTO',
                                        'prefix_length' => 1,
                                        'max_expansions' => 50,
                                        'boost' => 2,
                                    ],
                                ],
                            ],
                        ],
                        'tie_breaker' => 0.3,
                    ],
                ],
                'sort' => [
                    ['_score' => ['order' => 'desc']],
                    ['kategoriId' => ['order' => 'desc']],
                ],
            ],
        ])->asArray();

        return [
            'total' => (int)($resp['hits']['total']['value'] ?? 0),
            'results' => array_map(
                static function (array $hit): array {
                    $src = $hit['_source'] ?? [];

                    return [
                        'kategoriId' => (string)($src['kategoriId'] ?? ''),
                        'name' => (string)($src['name'] ?? ''),
                        'url' => (string)($src['url'] ?? ''),
                    ];
                },
                $resp['hits']['hits'] ?? []
            ),
        ];
    }
}
