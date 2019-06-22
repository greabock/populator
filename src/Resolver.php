<?php

namespace Greabock\Populator;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;

class Resolver
{
    /**
     * @var IdentityMap
     */
    private $identityMap;
    /**
     * @var Application
     */
    private $app;

    /**
     * Resolver constructor.
     * @param Application $app
     * @param IdentityMap $map
     */
    public function __construct(Application $app, IdentityMap $map)
    {
        $this->identityMap = $map;
        $this->app = $app;
    }

    public function resolve($model, $data): Model
    {
        $model = is_string($model) ? new $model : $model;

        return $this->find($model, $data) ?? $this->build($model, $data);
    }

    public function getCached(Model $model, $data): ?Model
    {
        return $this->identityMap->get($this->identityMap->resolveHashName($model, $data));
    }

    public function build(Model $model, $data): Model
    {
        return $this->identityMap[$this->identityMap->resolveHashName($model, $data)] = $model;
    }

    public function find(Model $model, $data): ?Model
    {
        if (!isset($data[$model->getKeyName()])) {
            return null;
        }

        return $this->getCached($model, $data) ?? $this->findInDataBase($model, $data);
    }

    public function loadRelation(Model $model, string $relation)
    {
        return $this->identityMap->loadRelation($model, $relation);
    }

    public function persist(Model $model)
    {
        $this->identityMap->persist($model);
    }

    private function findInDataBase(Model $model, $data)
    {
        if (isset($data[$model->getKeyName()])) {
            $resultModel = $model->newQuery()->find($data[$model->getKeyName()]);
            if ($resultModel) {
                $this->identityMap->remember($resultModel);
                return $resultModel;
            }
        }

        return null;
    }
}