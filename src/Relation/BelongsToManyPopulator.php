<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BelongsToManyPopulator extends RelationPopulator
{
    /**
     * @param Model $model
     * @param Relation|BelongsToMany $relation
     * @param array|null $data
     * @param string $relationName
     */
    public function populate(Model $model, Relation $relation, ?array $data, string $relationName): void
    {
        // If no data provided, just do nothing, because of this is "to many" relation,
        // and we cant set this relation to null.
        // Todo: should exception be thrown?
        if (is_null($data)) {
            return;
        }

        $relatedCollection = collect($data)->map(function ($relatedData) use ($relation, $model) : ?Model {
            $relatedModel = $this->resolver->findInDataBase($relation->getRelated(), $relatedData);
            return $relatedModel ? tap($relatedModel, function (Model $relatedModel) use ($model, $relation, $relatedData) {
                $relatedModel->setRelation('pivot', $relation->newPivot(
                    array_merge(Arr::get($relatedData, 'pivot', []), [
                        $relation->getForeignPivotKeyName() => $model->{$relation->getParentKeyName()},
                        $relation->getRelatedPivotKeyName() => $relatedModel->{$relation->getRelatedKeyName()},
                    ]), $model->exists
                ));
            }) : null;
        });

        // Build authentic collection for model.
        $relatedCollection = $relation->getQuery()->getModel()->newCollection($relatedCollection->filter()->all());

        // Pivot may have foreign keys, so we should sync models after all related models persisted.
        // Use onFlush method for it.
        $this->uow->onFlush(function () use ($relation, $relatedCollection, $model) {
            $relation->sync($relatedCollection->mapWithKeys(function (Model $relatedModel) {
                return [$relatedModel->getKey() => $relatedModel->getRelation('pivot')->toArray()];
            })->toArray());

            // Renew all models from database after sync.
            $relatedCollection->each(function (Model $model): void {
                $model->refresh();
            });
        });

        $model->setRelation(Str::snake($relationName), $relatedCollection);
    }
}
