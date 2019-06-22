<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BelongsToPopulator extends RelationPopulator
{

    function populate(Model $model, string $relationName, ?array $data)
    {
        /** @var BelongsTo $relation */
        $relation = $model->{$relationName}();

        $related = $this->resolver->resolve($relation->getRelated(), $data);

        $this->fillRelationField($model, $relation, $related);
    }

    protected function fillRelationField(Model $model, BelongsTo $relation, Model $related)
    {
        $model->{$relation->getForeignKeyName()} = $related->{$relation->getOwnerKeyName()};
    }
}