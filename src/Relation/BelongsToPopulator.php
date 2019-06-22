<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BelongsToPopulator extends RelationPopulator
{

    function populate(Model $model, string $relationName, ?array $data)
    {
        /** @var BelongsTo $relation */
        $relation = $model->{$relationName}();

        $related = $this->resolver->resolve($relation->getRelated(), $data);

        $this->fillRelationField($model, $relation, $related);

        $this->uow->execute(function () use ($model, $related, $relationName) {
            $model->setRelation(Str::snake($relationName), $related);
        });
    }

    protected function fillRelationField(Model $model, BelongsTo $relation, Model $related)
    {
        $model->{$relation->getForeignKeyName()} = $related->{$relation->getOwnerKeyName()};
    }
}