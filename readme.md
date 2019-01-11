[![Build](https://travis-ci.org/tsukasa-mixer/QueryBuilder.svg?branch=master)](https://packagist.org/packages/tsukasa/query_builder)
[![Latest Stable Version](https://poser.pugx.org/tsukasa/query_builder/v/stable)](https://packagist.org/packages/tsukasa/query_builder)
[![Total Downloads](https://poser.pugx.org/tsukasa/query_builder/downloads)](https://packagist.org/packages/tsukasa/query_builder)
[![License](https://poser.pugx.org/tsukasa/query_builder/license)](https://github.com/tsukasa/query_builder)
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Ftsukasa-mixer%2FQueryBuilder.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Ftsukasa-mixer%2FQueryBuilder?ref=badge_shield)

# Setup
```
composer require tsukasa/query_builder
```

#Basic usage

Detail doc:
- [RUS](doc/ru/main.md)

```php
use Tsukasa\QueryBuilder\QueryBuilder

require('vendor/autoload.php'); // Composer autoloader

$connection = DriverManager::getConnection([
        'dbname' => 'mydb',
        'user' => 'user',
        'password' => 'secret',
        'host' => 'localhost',
        'driver' => 'pdo_mysql',
    ], 
    $config = new \Doctrine\DBAL\Configuration()
);


$qb = QueryBuilder::getInstance($connection);
$qb->setTypeSelect()
    ->setSelect('*')
    ->setFrom('comment')
    ->setWhere(['id__gte' => 1])
    ->setOrder(['created_at']);

$connection->query($qb->toSQL())->fetchAll();
// SELECT * FROM comment WHERE id >= 1 ORDER BY created_at ASC
```

## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Ftsukasa-mixer%2FQueryBuilder.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2Ftsukasa-mixer%2FQueryBuilder?ref=badge_large)
