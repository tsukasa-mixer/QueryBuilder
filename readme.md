

* **Subject:** SQL Query builder
* **Syntax:** Django-like lookups
* **Documentation:** **[Russian](./docs/ru/readme.md)**
* **PHP version:** 5.6+
* **State:** [![Build](https://travis-ci.org/tsukasa-mixer/QueryBuilder.svg?branch=master)](https://packagist.org/packages/tsukasa/query_builder) [![Coverage Status](https://coveralls.io/repos/github/tsukasa-mixer/QueryBuilder/badge.svg?branch=master)](https://coveralls.io/github/tsukasa-mixer/QueryBuilder?branch=master)
* **Version:** [![Latest Stable Version](https://poser.pugx.org/tsukasa/query_builder/v/stable)](https://packagist.org/packages/tsukasa/query_builder)
* **Packagist:** [tsukasa/QueryBuilder](https://packagist.org/packages/tsukasa/query_builder) [![Total Downloads](https://poser.pugx.org/tsukasa/query_builder/downloads)](https://packagist.org/packages/tsukasa/query_builder)
* **Composer:** `composer require tsukasa/query_builder`
* **License:** [![License](https://poser.pugx.org/tsukasa/query_builder/license)](https://github.com/tsukasa/query_builder) [![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2Ftsukasa-mixer%2FQueryBuilder.svg?type=shield)](https://app.fossa.io/projects/git%2Bgithub.com%2Ftsukasa-mixer%2FQueryBuilder?ref=badge_shield)

# Basic usage

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

$connection->fetchAll($qb->toSQL());
// SELECT * FROM comment WHERE id >= 1 ORDER BY created_at ASC
```
