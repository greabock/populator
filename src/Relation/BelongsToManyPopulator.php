<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class BelongsToManyPopulator extends RelationPopulator
{
    function populate(Model $model, string $relationName, ?array $data): void
    {
        if (is_null($data)) {
            return;
        }

        /** @var BelongsToMany $relation */
        $relation = $model->{$relationName}();
        $relatedModels = $relation->getQuery()
            ->getModel()->newCollection()->concat(
                array_filter(array_map(function ($relatedData) use ($relation, $model) : ?Model {
                    $relatedModel = $this->resolver->findInDataBase($relation->getRelated(), $relatedData);
                    if (!is_null($relatedModel)) {
                        $relatedModel->setRelation('pivot', $relation->newPivot(
                            array_merge(Arr::get($relatedData, 'pivot', []), [
                                $relation->getForeignPivotKeyName() => $model->{$relation->getParentKeyName()},
                                $relation->getRelatedPivotKeyName() => $relatedModel->{$relation->getRelatedKeyName()},
                            ]),
                            $relatedModel->exists
                        ));
                    }
                    return $relatedModel;
                }, $data))
            );


        $this->uow->onFlush(function () use ($relation, $relatedModels, $model, $relationName) {
            $relation->sync($relatedModels->mapWithKeys(function (Model $relatedModel) {
                return [
                    $relatedModel->getKey() => $relatedModel->getRelation('pivot')->toArray(),
                ];
            })->toArray());
            $relatedModels->each(function (Model $model): void {
                $model->refresh();
            });
        });

        $model->setRelation(Str::snake($relationName), $relatedModels);
    }
}