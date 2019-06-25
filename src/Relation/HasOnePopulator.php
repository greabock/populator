<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class HasOnePopulator extends RelationPopulator
{
    function populate(Model $model, string $relationName, ?array $data): void
    {
        /** @var ?Model $existsModel */
        $existsModel = $this->resolver->loadRelation($model, $relationName);

        /** @var HasMany $relation */
        $relation = call_user_func([$model, $relationName]);

        /** @var ?Model $relatedModel */
        $relatedModel = $this->populator->populate(get_class($relation->getRelated()), $data);

        if (!is_null($existsModel) && !$existsModel->is($relatedModel)) {
            $this->uow->destroy($existsModel);
        }

        if (!is_null($relatedModel)) {
            $this->fillRelationField($relatedModel, $relation, $model);
            $this->uow->persist($relatedModel);
        }

        $model->setRelation(Str::snake($relationName), $relatedModel);
    }

    protected function fillRelationField(Model $model, HasOne $relation, Model $related): void
    {
        $model->{$relation->getForeignKeyName()} = $related ? $related->{$relation->getLocalKeyName()} : null;
    }
}