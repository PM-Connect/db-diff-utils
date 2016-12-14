# Database Diff Utils [![Build Status](https://travis-ci.org/PM-Connect/db-diff-utils.svg)](https://travis-ci.org/PM-Connect/db-diff-utils) [![Latest Stable Version](https://poser.pugx.org/pm-connect/db-diff-utils/v/stable)](https://packagist.org/packages/pm-connect/db-diff-utils) [![Total Downloads](https://poser.pugx.org/pm-connect/db-diff-utils/downloads.svg)](https://packagist.org/packages/pm-connect/db-diff-utils) [![Latest Unstable Version](https://poser.pugx.org/pm-connect/db-diff-utils/v/unstable.svg)](https://packagist.org/packages/pm-connect/db-diff-utils) [![License](https://poser.pugx.org/pm-connect/db-diff-utils/license.svg)](https://packagist.org/packages/pm-connect/db-diff-utils)

## Looking For An All-In-One DB Structure Diff Tool?

If you're looking for an all-in-one database diff tool, checkout [DB Diff](https://github.com/PM-Connect/db-diff)

## Intro

A simple set of utilities to be used for generating a diff between 2 database connections and their structures (not data).

Also contains a Laravel service provider and console command that can diff 2 given databases.

- [Installation](#installation)
    - [Laravel Service Provider](#optional-add-the-laravel-service-provider)
- [Usage](#usage)
    - [Using The Provided Objects](#using-the-provided-objects)
    - [Using The Artisan Console Command](#using-the-laravel-artisan-console-command)

## Installation

Install through composer.

```
composer require pm-connect/db-diff-utils 
```

### *Optional* Add The Laravel Service Provider

Add the following line to your `config/app.php` file within the providers array if you wish to use the provided artisan command.

```
...
PMConnect\DBDiff\Utils\DBDiffUtilServiceProvider::class,
...
```

## Usage

The provided diff utilities can be used in 2 ways.

1. Using the provided objects where you need them.
2. Using the provided artisan console command `db:diff`.

### Using The Provided Objects

The package provides the following objects for your use:

#### `PMConnect\DBDiff\Utils\Diff`

This object provides a basic wrapper to the other available objects and is capable of running a full diff.

##### Example Use

The `Diff` object accepts in the following as constructor parameters.

1. `PMConnect\DBDiff\Utils\Contracts\Output`
    - Create an implementation of this to save the output of the diff.
2. `Illuminate\Database\Connection`
    - Provide connection instances to the databases you wish to diff.
    - This can be done easily using `Illuminate\Database\Connectors\ConnectionFactory`
    - This can also be done by manually creating instances of the connections you wish to use.
        - `Illuminate\Database\MySqlConnection`
        - `Illuminate\Database\PostgresConnection`
        - `Illuminate\Database\SQLiteConnection`

```php
<?php

use PMConnect\DBDiff\Utils\Diff;
use Illuminate\Database\MySqlConnection;

$config1 = [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '3306,
    'database' => 'some_database',
    'collation' => 'utf8mb4_general_ci',
    'username' => 'username',
    'password' => 'password'
];

$config2 = [
    'driver' => 'mysql',
    'host' => '127.0.0.1',
    'port' => '3306,
    'database' => 'some_other_database',
    'collation' => 'utf8mb4_general_ci',
    'username' => 'username',
    'password' => 'password'
];

// Connect to the required databases.
$dsn1 = "{$config1['driver']}:host={$config1['host']};port={$config1['port']};dbname={$config1['database']}";
$pdo1 = new PDO($dsn1, $config1['username'], $config1['password']);

$dsn2 = "{$config2['driver']}:host={$config2['host']};port={$config2['port']};dbname={$config2['database']}";
$pdo2 = new PDO($dsn2, $config2['username'], $config2['password']);

$connection1 = new MySqlConnection($pdo1, $config1['database'], $prefix = '', $config1);
$connection2 = new MySqlConnection($pdo2, $config2['database'], $prefix = '', $config2);

// Create an instance of your Output implementation to inject.
$output = new YourImplementationOfOutputContract;

// Create a diff instance with the required injections.
$diff = new Diff(
    $output,
    $connection1,
    $connection2
);

// Run the diff
$diff->diff();
```

### Using The Laravel Artisan Console Command

This package provides an artisan console command that can diff 2 given databases for you and output the results into the console.

Provided you have added the service provider (as mentioned above) you can use the command as follows:

```
>>> php artisan db:diff --default-collation=utf8mb4_general_ci
```

This command will automatically prompt for the database connection details from you, then run the diff and output any errors to the screen.

The `--default-collation` option is optional and will default to 'utf8mb4_general_ci' if not provided, but can still be provided during the config of each database.

## Issues

Please submit any issues using GitHubs build in issue management.
