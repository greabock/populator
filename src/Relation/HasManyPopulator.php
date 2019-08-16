<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class HasManyPopulator extends RelationPopulator
{

    /**
     * @param Model $model
     * @param Relation|HasMany $relation
     * @param array|null $data
     * @param string $relationName
     */
    public function populate(Model $model, Relation $relation, ?array $data, string $relationName): void
    {
        if (is_null($data)) {
            return;
        }

        /** @var Collection $existsModels */
        $existsModels = $this->resolver->loadRelation($model, $relationName);

        $relatedModels = $relation->getQuery()->getModel()->newCollection()
            ->concat(array_map(function (array $modelData) use ($relation) {
                return $this->populator->populate(get_class($relation->getRelated()), $modelData);
            }, $data));

        $existsModels->filter(function (Model $existsModel) use ($relatedModels): bool {
            return $relatedModels->filter(function (Model $relatedModel) use ($existsModel): bool {
                return $existsModel->is($relatedModel);
            })->isEmpty();
        })->each(function (Model $model) use ($relation): void {
            $this->uow->destroy($model);
        });

        $relatedModels->each(function (Model $related) use ($relation, $model, $relationName): void {
            $this->setRelationField($model, $relation, $related);
            $this->uow->persist($related);
        });

        $model->setRelation(Str::snake($relationName), $relatedModels);
    }

    protected function setRelationField(Model $model, HasMany $relation, Model $related): void
    {
        $related->{$relation->getForeignKeyName()} = $model->{$relation->getLocalKeyName()};
    }
}
