<?php

namespace Greabock\Populator;

use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Collection;

class IdentityMap
{
    protected array $trackedRelations = [];

    private Collection $heap;

    public function __construct()
    {
        $this->heap = new Collection();
    }

    private function resolveHashName(Model $model, ?array $data = null): ?string
    {
        if ($key = $this->resolveKey($model, $data)) {
            return get_class($model) . '#' . $key;
        }

        return null;
    }

    private function resolveKey(Model $model, ?array $data = null): ?string
    {
        $primaryKeyName = $model->getKeyName();

        if ($data && isset($data[$primaryKeyName])) {
            return $data[$primaryKeyName];
        }

        return $model->getKey();
    }

    public function track(Model|EloquentCollection|null $relation): void
    {
        if (is_null($relation) || $relation instanceof Pivot) {
            return;
        }

        if ($relation instanceof EloquentCollection) {

            $relation->each([$this, 'track']);

            return;
        }

        foreach ($relation->getRelations() as $key => $nestedRelation) {
            $this->trackRelation($relation, $key, $nestedRelation);
        }

        $this->remember($relation);
    }

    public function trackRelation($relation, $key, $nestedRelation): void
    {
        $hash = $this->resolveRelationHashName($relation, $key);

        if (!$this->isTrackedRelation($hash)) {

            $this->track($nestedRelation);

            $this->markTracked($hash);
        }
    }

    public function remember(Model $model): void
    {
        $hashName = $this->resolveHashName($model);

        $this->heap->put($hashName, $model);
    }

    protected function isTrackedRelation($key): bool
    {
        return in_array($key, $this->trackedRelations);
    }

    private function resolveRelationHashName(Model $model, $relationName): string
    {
        return $this->resolveHashName($model) . '#' . $relationName;
    }

    private function markTracked($key): void
    {
        $this->trackedRelations[] = $key;
    }

    public function get(Model $model, ?array $data)
    {
        return $this->heap->get($this->resolveHashName($model, $data));
    }

    public function forget(Model $model)
    {
        $this->heap->forget($this->resolveHashName($model));
    }

    public function clear()
    {
        $this->heap = new Collection();
    }

    public function models(): Collection
    {
        return $this->heap;
    }
}
