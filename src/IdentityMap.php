<?php

namespace Greabock\Populator;


use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Ramsey\Uuid\Uuid;

class IdentityMap extends Collection
{

    public function __construct()
    {
        parent::__construct();
    }

    public function resolveHashName(Model $model, ?array $data = null): string
    {
        return get_class($model) . '#' . $this->resolveKey($model, $data);
    }

    protected function resolveKey(Model $model, ?array $data = null): string
    {
        if ($data && isset($data[$model->getKeyName()])) {
            return $data[$model->getKeyName()];
        }

        if (!$model->getKey()) {
            $model->{$model->getKeyName()} = $this->generateKey();
        }

        return $model->getKey();
    }

    protected function generateKey(): string
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * @param Model $model
     * @param string $relationName
     * @return Model|EloquentCollection|Model[]
     */
    public function loadRelation(Model $model, string $relationName)
    {
        $model->load($relationName);
        if ($model->{$relationName} instanceof EloquentCollection) {
            foreach ($model->{$relationName} as $relatedModel) {
                $this[$this->resolveHashName($relatedModel)] = $relatedModel;
            }
        } elseif (!is_null($model->{$relationName})) {
            $this[$this->resolveHashName($model->{$relationName})] = $model->{$relationName};
        }

        return $model->{$relationName};
    }

    public function remember(Model $model): void
    {
        $this[$this->resolveHashName($model)] = $model;
        foreach ($model->getRelations() as $relation) {
            if ($relation instanceof EloquentCollection) {
                foreach ($relation as $relationModel) {
                    $this->remember($relationModel);
                }
            }

            if ($relation instanceof Model) {
                $this->remember($relation);
            }
        }
    }
}