<?php

namespace hi019\LaravelTypesense;

use Illuminate\Support\Facades\App;
use Typesense\Client;
use Laravel\Scout\EngineManager;
use Illuminate\Support\ServiceProvider;
use hi019\LaravelTypesense\Engines\TypesenseSearchEngine;
use Laravel\Scout\Builder;

class TypesenseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(
            Client::class,
            function () {
                return new Client(
                    [
                        'api_key'                       => config('scout.typesense.api_key', ''),
                        'nodes'                         => config('scout.typesense.nodes', []),
                        'nearest_node'                  => config('scout.typesense.nearest_node', []),
                        'connection_timeout_seconds'    => config('scout.typesense.connection_timeout_seconds', 2.0),
                        'healthcheck_interval_seconds'  => config('scout.typesense.healthcheck_interval_seconds', 60),
                        'num_retries'                   => config('scout.typesense.num_retries', 3),
                        'retry_interval_seconds'        => config('scout.typesense.retry_interval_seconds', 1.0),
                    ]
                );
            }
        );
    }

    public function boot(): void
    {
        $this->app[EngineManager::class]->extend(
          'typesense', function (App $app) {
              return new TypesenseSearchEngine(new Typesense($app->make(Client::class)), config('scout.soft_delete'));
          }
        );

        Builder::macro(
          'count',
          function () {
              return $this->engine()->getTotalCount(
                $this->engine()->search($this)
              );
          }
        );
    }
}
