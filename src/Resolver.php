<?php

namespace Greabock\Populator;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Application;
use InvalidArgumentException;

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

    /**
     * @param string|Model $model
     * @param array $data
     * @return Model
     */
    public function resolve($model, array $data): Model
    {
        $model = $this->resolveModel($model);

        return $this->find($model, $data) ?? $this->build($model, $data);
    }

    public function getCached(Model $model, array $data): ?Model
    {
        return $this->identityMap->get($this->identityMap->resolveHashName($model, $data));
    }

    public function build(Model $model, array $data): Model
    {
        return $this->identityMap[$this->identityMap->resolveHashName($model, $data)] = $model;
    }

    public function find(Model $model, array $data): ?Model
    {
        if (!isset($data[$model->getKeyName()])) {
            return null;
        }

        return $this->getCached($model, $data) ?? $this->findInDataBase($model, $data);
    }

    /**
     * @param Model $model
     * @param string $relation
     * @return Collection|Model|Model[]
     */
    public function loadRelation(Model $model, string $relation)
    {
        return $this->identityMap->loadRelation($model, $relation);
    }

    private function findInDataBase(Model $model, array $data): ?Model
    {
        if (isset($data[$model->getKeyName()])) {
            $resultModel = $model->newQuery()->find($data[$model->getKeyName()]);
            if ($resultModel instanceof Model) {
                $this->identityMap->remember($resultModel);
                return $resultModel;
            }
        }

        return null;
    }

    /**
     * @param mixed|Model|string $model
     * @return Model
     * @throws InvalidArgumentException
     */
    private function resolveModel($model): Model
    {
        switch (true) {
            case is_object($model) && $model instanceof Model:
                return $model;
            case is_string($model) && is_subclass_of($model, Model::class):
                /**
                 * @psalm-var class-string $model
                 * @psalm-suppress LessSpecificReturnStatement
                 */
                return new $model;
            default:
                throw new InvalidArgumentException('Argument $model should be instance or subclass of ' . Model::class);
        }
    }
}
