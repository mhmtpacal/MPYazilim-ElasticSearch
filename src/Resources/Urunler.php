<?php

namespace MPYazilim\Elastic\Resources;

final class Urunler extends AbstractResource
{
    protected string $base = 'urunler';

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
            ],
            'mappings' => [
                'dynamic' => false,
                'properties' => [
                    'urunId' => ['type' => 'integer'],
                    'name' => ['type' => 'text', 'analyzer' => 'tr_analyzer'],
                    'name_suggest' => ['type' => 'search_as_you_type'],
                    'aciklama' => ['type' => 'text', 'analyzer' => 'tr_analyzer'],
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
            if (!isset($item['urunId'])) continue;

            $body[] = [
                'index' => [
                    '_index' => $newIndex,
                    '_id' => (string)$item['urunId']
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
                                        'name_suggest^8',
                                        'name_suggest._2gram^6',
                                        'name_suggest._3gram^4',
                                    ],
                                ],
                            ],
                            [
                                'multi_match' => [
                                    'query' => $q,
                                    'fields' => ['name^6', 'aciklama^2'],
                                    'operator' => 'and',
                                ],
                            ],
                            [
                                'multi_match' => [
                                    'query' => $q,
                                    'fields' => ['name^4', 'aciklama'],
                                    'fuzziness' => 'AUTO',
                                    'prefix_length' => 1,
                                    'max_expansions' => 50,
                                ],
                            ],
                        ],
                        'tie_breaker' => 0.3,
                    ],
                ],
                'sort' => [
                    ['_score' => ['order' => 'desc']],
                    ['urunId' => ['order' => 'desc']]
                ]
            ]
        ])->asArray();

        return array_map(
            fn($h) => $h['_source']['urunId'],
            $resp['hits']['hits'] ?? []
        );
    }
}
