<?php

namespace Greabock\Populator;


use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class IdentityMap extends Collection
{
    protected $trackedRelations = [];

    /**
     * @param Model $model
     * @param array|null $data
     * @return string
     */
    public static function resolveHashName(Model $model, ?array $data = null): string
    {
        return get_class($model) . '#' . static::resolveKey($model, $data);
    }

    /**
     * @param Model $model
     * @param array|null $data
     * @return string
     */
    protected static function resolveKey(Model $model, ?array $data = null): string
    {
        $primaryKeyName = Resolver::resolveKeyName($model);

        if ($data && isset($data[$primaryKeyName])) {
            return $data[$primaryKeyName];
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