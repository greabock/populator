<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HasOnePopulator extends RelationPopulator
{
    function populate(Model $model, string $relationName, ?array $data)
    {
        /** @var Model $existsModels */
        $existsModel = $this->resolver->loadRelation($model, $relationName);

        /** @var HasMany $relation */
        $relation = $model->{$relationName}();

        $relatedModel = $this->populator->populate($relation->getRelated(), $data);

        if ($existsModel && (!$relatedModel || !$relatedModel->is($existsModels))) {
            $this->uow->destroy($existsModel);
            return;
        }

        $existsModel->save();
    }
}