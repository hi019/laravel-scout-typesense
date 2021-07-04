*This is a fork of https://github.com/devloopsnet/laravel-scout-typesense-engine*

# Use https://github.com/typesense/laravel-scout-typesense-engine instead!

# Laravel Scout Typesense Engine
Typesense engine for laravel/scout https://github.com/typesense/typesense .

<p align="center">
    <img src="https://banners.beyondco.de/Typesense%20Driver%20for%20Laravel-Scout.png?theme=dark&packageName=devloopsnet%2Flaravel-typesense&pattern=anchorsAway&style=style_1&description=A+Typesense+%28search+engine%29+driver+for+laravel-scout&md=1&showWatermark=0&fontSize=100px&images=https%3A%2F%2Flaravel.com%2Fimg%2Flogomark.min.svg" alt="laravel-scout-typesense-engine
 socialcard">
</p>
This package makes it easy to add full text search support to your models with Laravel 5.3 to 7.0.

## Contents

- [Installation](#installation)
- [Usage](#usage)
- [Author](#author)
- [License](#license)


## Installation

You can install the package via composer:

``` bash
composer require hi019/laravel-scout-typesense
```

Add the service provider:

```php
// config/app.php
'providers' => [
    // ...
    hi019\LaravelTypesense\TypesenseServiceProvider::class,
],
```

Ensure you have Laravel Scout as a provider too otherwise you will get an "unresolvable dependency" error

```php
// config/app.php
'providers' => [
    // ...
    Laravel\Scout\ScoutServiceProvider::class,
],
```

Add  `SCOUT_DRIVER=typesense` to your `.env` file

Then you should publish `scout.php` configuration file to your config directory

```bash
php artisan vendor:publish --provider="Laravel\Scout\ScoutServiceProvider"
```

In your `config/scout.php` add:

```php

'typesense' => [
    'api_key'         => 'abcd',
    'nodes'           => [
      [
        'host'     => 'localhost',
        'port'     => '8108',
        'path'     => '',
        'protocol' => 'http',
      ],
    ],
    'nearest_node'    => [
        'host'     => 'localhost',
        'port'     => '8108',
        'path'     => '',
        'protocol' => 'http',
    ],
    'connection_timeout_seconds'   => 2,
    'healthcheck_interval_seconds' => 30,    
    'num_retries'                  => 3,
    'retry_interval_seconds'       => 1,
  ],
```

## Usage

After you have installed scout and the Typesense driver, you need to add the
`Searchable` trait to your models that you want to make searchable. Additionaly,
define the fields you want to make searchable by defining the `toSearchableArray` method on the model and implement `TypesenseModel`:

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use hi019\LaravelTypesense\Interfaces\TypesenseModel;
use Laravel\Scout\Searchable;

class Post extends Model implements TypesenseModel
{
    use Searchable;

    /**
     * Get the indexable data array for the model.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Customize array...

        return $array;
    }

    public function getCollectionSchema(): array {
      return [
        'name' => $this->searchableAs(),
        'fields' => [
          [
            'name' => 'title',
            'type' => 'string',
          ],
          [
            'name' => 'created_at',
            'type' => 'int32',
          ],
        ],
        'default_sorting_field' => 'created_at',
      ];
    }

    public function typesenseQueryBy(): array {
      return [
        'name',
      ];
    }
    
}
```

Then, sync the data with the search service like:

`php artisan scout:import App\\Post`

After that you can search your models with:

`Post::search('Bugs Bunny')->get();`

## Adding via Query
The `searchable()` method will chunk the results of the query and add the records to your search index. 

`$post = Post::find(1);`

// You may also add record via collection...
`$post->searchable();`

// OR

`$posts = Post::where('year', '>', '2018')->get();`

// You may also add records via collections...
`$posts->searchable();`

## Author

- [Abdullah Al-Faqeir](https://github.com/abdullahfaqeir)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
