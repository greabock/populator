<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BelongsToPopulator extends RelationPopulator
{
    function populate(Model $model, string $relationName, ?array $data): void
    {
        /** @var BelongsTo $relation */
        $relation = $model->{$relationName}();

        $related = $data ? $this->resolver->resolve($relation->getRelated(), $data) : null;

        $this->fillRelationField($model, $relation, $related);

        $model->setRelation(Str::snake($relationName), $related);
    }

    protected function fillRelationField(Model $model, BelongsTo $relation, ?Model $related): void
    {
        $model->{$relation->getForeignKeyName()} = $related ? $related->{$relation->getOwnerKeyName()} : null;
    }
}