<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HasManyPopulator extends RelationPopulator
{

    public function populate(Model $model, string $relationName, ?array $data)
    {
        if (!$data) {
            return;
        }

        /** @var Collection $existsModels */
        $existsModels = $this->resolver->loadRelation($model, $relationName);

        /** @var HasMany $relation */
        $relation = $model->{$relationName}();

        $relatedModels = collect(array_map(function (array $modelData) use ($relation) {
            return $this->populator->populate(get_class($relation->getRelated()), $modelData);
        }, $data));

        $existsModels->filter(function (Model $existsModel) use ($relatedModels) {
            return $relatedModels->filter(function (Model $relatedModel) use ($existsModel) {
                return $existsModel->is($relatedModel);
            })->isEmpty();
        })->each(function (Model $model) use ($relation) {
            $this->uow->destroy($model);
            // TODO: ON DETACH DESTROY, ON DETACH SET NULL ?
        });

        $relatedModels->each(function (Model $related) use ($relation, $model, $relationName) {
            $this->setRelationField($model, $relation, $related);
            $this->uow->persist($related);
            $this->uow->execute(function () use ($model, $relationName) {
                $model->load($relationName);
            });
        });
    }

    protected function setRelationField(Model $model, HasMany $relation, Model $related)
    {
        $related->{$relation->getForeignKeyName()} = $model->{$relation->getLocalKeyName()};
    }
}