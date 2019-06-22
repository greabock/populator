<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;

class BelongsToManyPopulator extends RelationPopulator
{
    function populate(Model $model, string $relationName, ?array $data)
    {
        /** @var BelongsToMany $relation */
        $relation = $model->{$relationName}();

        $relatedModels = collect($data)->map(function ($relatedData) use ($relation, $model) {
            $relatedModel = $this->resolver->resolve($relation->getRelated(), $relatedData);
            $relatedModel->setRelation('pivot', $relation->newPivot(
                array_merge(Arr::get($relatedData, 'pivot', []), [
                    $relation->getForeignPivotKeyName() => $model->{$relation->getParentKeyName()},
                    $relation->getRelatedPivotKeyName() => $relatedModel->{$relation->getRelatedKeyName()},
                ]),
                $relatedModel->exists
            ));

            return $relatedModel;
        });

        $relatedModels->each(function (Model $data) {
            $this->uow->persist($data);
        });

        $this->uow->execute(function () use ($relation, $relatedModels) {
            $relation->sync($relatedModels->mapWithKeys(function (Model $model) {
                return [
                    $model->getKey() => $model->getRelation('pivot')->toArray(),
                ];
            }));
        });
    }
}