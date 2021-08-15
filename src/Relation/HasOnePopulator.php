<?php

namespace Greabock\Populator\Relation;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Str;

class HasOnePopulator extends RelationPopulator
{
    /**
     * @param Model $model
     * @param Relation|HasOne $relation
     * @param array|null $data
     * @param string $relationName
     * @throws \Exception
     */
    public function populate(Model $model, Relation $relation, ?array $data, string $relationName): void
    {
        $existsModel = $this->resolver->loadRelation($model, $relationName);

        /** @var ?Model $relatedModel */
        $relatedModel = $this->populator->populate(get_class($relation->getRelated()), $data);

        if (!is_null($existsModel) && !$existsModel->is($relatedModel)) {
            $this->uow->destroy($existsModel);
        }

        if (!is_null($relatedModel)) {
            $this->setRelationField($relatedModel, $relation, $model);
            $this->uow->persist($relatedModel);
        }

        $model->setRelation(Str::snake($relationName), $relatedModel);
    }

    protected function setRelationField(Model $model, HasOne $relation, Model $related): void
    {
        $model->{$relation->getForeignKeyName()} = $related ? $related->{$relation->getLocalKeyName()} : null;
    }
}
