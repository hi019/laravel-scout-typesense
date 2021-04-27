<?php

namespace hi019\LaravelTypesense\Interfaces;

interface TypesenseModel
{

    public function typesenseQueryBy(): array;

    public function getCollectionSchema(): array;

}