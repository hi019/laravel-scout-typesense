<?php

namespace hi019\LaravelTypesense\Interfaces;

interface TypesenseSearch
{

    public function typesenseQueryBy(): array;

    public function getCollectionSchema(): array;

}