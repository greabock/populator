<?php

namespace Greabock\Populator;


use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class IdentityMap extends Collection
{
    protected $trackedRelations = [];

    public static function resolveHashName(Model $model, ?array $data = null): string
    {
        return get_class($model) . '#' . static::resolveKey($model, $data);
    }

    protected static function resolveKey(Model $model, ?array $data = null): string
    {
        if ($data && isset($data[$model->getKeyName()])) {
            return $data[$model->getKeyName()];
        }

        return $model->getKey();
    }

    public function remember($relation)
    {
        if ($relation instanceof Pivot) {
            return null;
        }

        if ($relation instanceof EloquentCollection) {
            return $relation->map(function (Model $model) {
                return $this->remember($model);
            })->toArray();
        }

        foreach ($relation->getRelations() as $key => $nestedRelation) {
            if ($this->isTrackedRelation(static::resolveRelationHashName($relation, $key))) {
                continue;
            }
            $this->remember($nestedRelation);
            $this->markTracked(static::resolveRelationHashName($relation, $key));
        }

        $hashName = static::resolveHashName($relation);
        $this[$hashName] = $relation;

        return $hashName;
    }

    public function isTrackedRelation($key)
    {
        return in_array($key, $this->trackedRelations);
    }

    public static function resolveRelationHashName(Model $model, $relationName)
    {
        return static::resolveHashName($model) . '#' . $relationName;
    }

    public function markTracked($key)
    {
        return $this->trackedRelations[] = $key;
    }
}