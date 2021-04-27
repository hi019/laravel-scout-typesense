<?php

namespace hi019\LaravelTypesense;

use Typesense\Client;
use Typesense\Document;
use Typesense\Collection;
use GuzzleHttp\Exception\GuzzleException;
use Typesense\Exceptions\ObjectNotFound;
use Typesense\Exceptions\TypesenseClientError;

class Typesense
{

    /**
     * @var \Typesense\Client
     */
    private $client;

    /**
     * Typesense constructor.
     *
     * @param   \Typesense\Client  $client
     */
    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return \Typesense\Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * @param   \Illuminate\Database\Eloquent\Model|\hi019\LaravelTypesense\Interfaces\TypesenseModel  $model
     *
     * @return \Typesense\Collection
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function getOrCreateCollectionFromModel($model): Collection
    {
        $index = $this->client->getCollections()->{$model->searchableAs()};
        try {
            $index->retrieve();

            return $index;
        } catch (ObjectNotFound $exception) {
            $this->client->getCollections()->create(
              $model->getCollectionSchema()
            );

            return $this->client->getCollections()->{$model->searchableAs()};
        }
    }

    /**
     * @param   \Illuminate\Database\Eloquent\Model  $model
     *
     * @return \Typesense\Collection
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getCollectionIndex($model): Collection
    {
        return $this->getOrCreateCollectionFromModel($model);
    }

    /**
     * @param   \Typesense\Collection  $collectionIndex
     * @param                                   $array
     *
     * @throws \Typesense\Exceptions\TypesenseClientError
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function upsertDocument(Collection $collectionIndex, $array): void
    {
        /**
         * @var $document Document
         */
        $document = $collectionIndex->getDocuments()[$array['id']];

        try {
            $document->retrieve();
            $document->delete();
            $collectionIndex->getDocuments()->create($array);
        } catch (ObjectNotFound $e) {
            $collectionIndex->getDocuments()->create($array);
        }
    }

    /**
     * @param \Typesense\Collection $collectionIndex
     * @param int                   $modelId
     * @throws GuzzleException
     * @throws TypesenseClientError
     */
    public function deleteDocument(Collection $collectionIndex, $modelId): void
    {
        /**
         * @var $document Document
         */
        $document = $collectionIndex->getDocuments()[(string)$modelId];
        $document->delete();
    }

    public function importDocuments(Collection $collectionIndex, $documents) {
        $collectionIndex->getDocuments()->import($documents);
    }
}