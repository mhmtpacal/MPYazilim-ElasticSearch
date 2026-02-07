# MPYazilim Elasticsearch

MPYazilim Elasticsearch, **multi-project / multi-domain** destekli,  
**alias + versioned index** mantığıyla çalışan, **zero-downtime rebuild** sağlayan hafif bir Elasticsearch wrapper’ıdır.

Her rebuild işleminde:
- yeni bir versioned index oluşturur
- alias’i atomik olarak yeni index’e taşır
- eski index’i **otomatik siler**

Disk şişmez, downtime olmaz.

## Özellikler
 - ✅ Zero-downtime rebuild
- ✅ Alias tabanlı routing

- ✅ Eski index otomatik silinir
- ✅ Multi-domain destekli
- ✅ Search-as-you-type
- ✅ Türkçe analyzer (lowercase + asciifolding)
- ✅ Framework bağımsız


## Gereksinimler

- PHP 8.1+
- Elasticsearch 8.x

elastic/elasticsearch PHP client
---

## Kurulum

```bash
composer require mpyazilim/elasticsearch
```


## Temel Kullanım

### Client Oluşturma
```bash
use MPYazilim\Elastic\MPElastic;

$elastic = MPElastic::account([
    'host'     => 'https://localhost',
    'port'     => 9200,
    'username' => 'elastic',
    'password' => 'changeme',
    'domain'   => 'hepsimoda.com.tr',
]);
```


## Ürünler

### Rebuild (Index Yenileme)

```bash
$elastic->urunler()->rebuild([
    [
        'urunId'    => 1,
        'name'      => 'Mini Kalpli Düğmeli Elbise',
        'name_suggest' => 'Mini Kalpli',
        'aciklama'  => 'Yazlık kadın elbisesi',
    ],
    [
        'urunId'    => 2,
        'name'      => 'Uzun Kollu Elbise',
        'name_suggest' => 'Uzun Kollu',
        'aciklama'  => 'Kışlık elbise',
    ],
]);
```

### Rebuild süreci:

- Yeni urunler_vYYYYMMDD_His index oluşturulur
- Veriler bulk ile yazılır
- Alias yeni index’e taşınır
- Eski index silinir

## Arama
```bash
$result = $elastic->urunler()->search('elbise');
```

- Benzerlik (_score) önceliklidir
- Skor eşitse urunId DESC ile sıralanır

## Kategoriler
### Rebuild (Index Yenileme)

```bash
$elastic->kategoriler()->rebuild([
    [
        'kategoriId' => 10,
        'name'       => 'Elbiseler',
        'name_suggest' => 'Elbise',
        'url'        => 'elbiseler',
    ],
    [
        'kategoriId' => 20,
        'name'       => 'Pantolonlar',
        'name_suggest' => 'Pantolon',
        'url'        => 'pantolonlar',
    ],
]);

```

## Arama
```bash
$categories = $elastic->kategoriler()->search('elbise');
```