<?php

namespace hi019\LaravelTypesense\Engines;

use Laravel\Scout\Builder;
use Laravel\Scout\Engines\Engine;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use hi019\LaravelTypesense\Typesense;
use GuzzleHttp\Exception\GuzzleException;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\TypesenseClientError;


class TypesenseSearchEngine extends Engine
{

    private $typesense;

    /**
     * TypesenseSearchEngine constructor.
     *
     * @param $typesense
     */
    public function __construct(Typesense $typesense)
    {
        $this->typesense = $typesense;
    }

    /**
     * @inheritDoc
     */
    public function update($models): void
    {
        $models->each(
          function (Model $model) {
              $array = $model->toSearchableArray();

              $collectionIndex = $this->typesense->getCollectionIndex($model);

              $this->typesense->upsertDocument($collectionIndex, $array);
          }
        );
    }

    /**
     * @inheritDoc
     */
    public function delete($models): void
    {
        $models->each(
          function (Model $model) {
              $collectionIndex = $this->typesense->getCollectionIndex($model);

              try {
                  $this->typesense->deleteDocument(
                    $collectionIndex,
                    $model->getScoutKey()
                  );
              } catch (ObjectNotFound $e) {
                  // Don't need to do anything here. The object wasn't found anyway, so nothing to delete!
              }
          }
        );
    }

    /**
     * @inheritDoc
     */
    public function search(Builder $builder)
    {
        return $this->performSearch(
          $builder,
          array_filter(
            [
              'q'        => $builder->query,
              'query_by' => implode(',', $builder->model->typesenseQueryBy()),
              'filter_by' => $this->filters($builder),
              'per_page' => $builder->limit,
              'page'     => 1,
            ]
          )
        );
    }

    /**
     * @inheritDoc
     */
    public function paginate(Builder $builder, $perPage, $page)
    {
        return $this->performSearch(
            $builder,
            array_filter(
                [
                    'q'        => $builder->query,
                    'query_by' => implode(',', $builder->model->typesenseQueryBy()),
                    'filter_by' => $this->filters($builder),
                    'per_page' => $perPage,
                    'page'     => $page,
                ]
            )
        );
    }

    /**
     * @param   \Laravel\Scout\Builder  $builder
     * @param   array                   $options
     *
     * @return array|mixed
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function performSearch(Builder $builder, array $options = [])
    {
        $documents =
          $this->typesense->getCollectionIndex($builder->model)->getDocuments();
        if ($builder->callback) {
            return call_user_func(
              $builder->callback,
              $documents,
              $builder->query,
              $options
            );
        }
        return $documents->search(
          $options
        );
    }

    /**
     * @param   \Laravel\Scout\Builder  $builder
     *
     * @return string
     */
    protected function filters(Builder $builder): string
    {
        return collect($builder->wheres)->map(
          static function ($value, $key) {
              return $key . ':=' . $value;
          }
        )->values()->implode(' && ');
    }

    /**
     * @inheritDoc
     */
    public function mapIds($results): Collection
    {
        return collect($results['hits'])->pluck('document.id')->values();
    }

    /**
     * @inheritDoc
     */
    public function map(Builder $builder, $results, $model)
    {
        if ((int)($results['found'] ?? 0) === 0) {
            return $model->newCollection();
        }

        $objectIds         =
          collect($results['hits'])->pluck('document.id')->values()->all();
        $objectIdPositions = array_flip($objectIds);
        return $model->getScoutModelsByIds(
          $builder,
          $objectIds
        )->filter(
          static function ($model) use ($objectIds) {
              return in_array($model->getScoutKey(), $objectIds, false);
          }
        )->sortBy(
          static function ($model) use ($objectIdPositions) {
              return $objectIdPositions[$model->getScoutKey()];
          }
        )->values();
    }

    /**
     * @inheritDoc
     */
    public function getTotalCount($results): int
    {
        return (int)($results['found'] ?? 0);
    }

    /**
     * @inheritDoc
     */
    public function flush($model): void
    {
        $collection = $this->typesense->getCollectionIndex($model);
        $collection->delete();
    }

}
