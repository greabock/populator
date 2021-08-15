<?php

namespace Greabock\Populator;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use Greabock\Populator\Contracts\KeyGeneratorInterface;

class Resolver
{
    public function __construct(
        private KeyGeneratorInterface $keyGenerator,
        private IdentityMap           $map
    )
    {
    }

    public function resolve(Model|string $model, array $data): Model
    {
        $model = $this->resolveModelInstance($model);

        return $this->find($model, $data) ?? $this->build($model, $data);
    }

    protected function getCached(Model $model, array $data): ?Model
    {
        return $this->map->get($model, $data);
    }

    protected function build(Model $model, array $data): Model
    {
        $model->{$model->getKeyName()} = $this->resolveKey($model, $data);

        $this->map->track($model);

        return $model;
    }

    protected function resolveKey(Model $model, array $data)
    {
        return $data[$model->getKeyName()] ?? $this->keyGenerator->generate($model);
    }

    public function find(Model $model, array $data): ?Model
    {
        return isset($data[$model->getKeyName()])
            ? ($this->getCached($model, $data) ?? $this->findInDataBase($model, $data))
            : null;
    }

    public function loadRelation(Model $model, string $relationName): Collection|Model|null
    {
        if (!$model->relationLoaded($relationName)) {
            $model->load($relationName);
        }

        $relation = $model->getRelation($relationName);

        if (!is_null($relation)) {
            $this->map->track($relation);
        }

        return $relation;
    }

    public function findInDataBase(Model $model, array $data): ?Model
    {
        $primaryKeyName = $model->getKeyName();

        if (isset($data[$primaryKeyName])) {
            $resultModel = $model->newQuery()->find($data[$primaryKeyName]);
            if ($resultModel instanceof Model) {
                $this->map->track($resultModel);
                return $resultModel;
            }
        }

        return null;
    }

    protected function resolveModelInstance(Model|string $model): Model
    {
        return match (true) {
            $model instanceof Model => $model,
            is_string($model) && is_subclass_of($model, Model::class) => new $model,
            default => throw new InvalidArgumentException('Argument $model should be instance or subclass of ' . Model::class),
        };
    }
}
